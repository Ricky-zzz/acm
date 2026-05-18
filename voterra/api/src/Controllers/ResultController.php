<?php

namespace App\Controllers;

use App\Crypto;
use App\Database;
use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResultController {

    private function getSetting($db, string $key): ?string {
        $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string)$value : null;
    }

    private function setSetting($db, string $key, string $value): void {
        $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        $stmt->execute([$key, $value]);
    }

    private function isExportLocked($db): bool {
        return $this->getSetting($db, 'export_locked') === '1';
    }

    private function getExportMethod(Request $request): string {
        $params = $request->getQueryParams();
        $method = strtolower((string)($params['method'] ?? 'manual'));
        return $method === '3g' ? '3g' : 'manual';
    }

    private function createExportKey(): string {
        return bin2hex(random_bytes(16));
    }

    private function fetchExportRows($db): array {
        $sql = "SELECT ballot_number, candidate_id FROM local_votes ORDER BY ballot_number ASC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    private function markTransmitted($db): void {
        $db->exec("UPDATE local_votes SET is_transmitted = 1 WHERE is_transmitted = 0");
    }

    private function insertExportLog($db, string $exportKey, int $expectedVotes, int $exportedVotes, string $method, string $status): void {
        $stmt = $db->prepare("INSERT INTO result_export_logs (export_key, expected_votes, exported_votes, method, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$exportKey, $expectedVotes, $exportedVotes, $method, $status]);
    }

    public function getStats(Request $request, Response $response) {
        $db = (new Database())->getConnection();

        $total = $db->query("SELECT COUNT(*) FROM authorized_ballots")->fetchColumn();
        $used = $db->query("SELECT COUNT(*) FROM authorized_ballots WHERE is_used = 1")->fetchColumn();
        $votes = $db->query("SELECT COUNT(*) FROM local_votes")->fetchColumn();

        $response->getBody()->write(json_encode([
            'total_ballots' => intval($total),
            'used_ballots' => intval($used),
            'total_votes' => intval($votes)
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getTally(Request $request, Response $response) {
        $db = (new Database())->getConnection();

        $sql = "SELECT c.id as candidate_id,
                       c.name as candidate_name,
                       p.title as position_title,
                       c.party_alias as party_alias,
                       COUNT(v.id) as vote_count
                FROM candidates c
                JOIN positions p ON c.position_id = p.id
                LEFT JOIN local_votes v ON v.candidate_id = c.id
                GROUP BY c.id
                ORDER BY p.id ASC, vote_count DESC";

        $stmt = $db->query($sql);
        $tally = $stmt->fetchAll();

        $response->getBody()->write(json_encode($tally));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getExportLogs(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $query = $request->getQueryParams();
        $method = strtolower((string)($query['method'] ?? ''));
        $params = [];

        $sql = "SELECT id, export_key, expected_votes, exported_votes, method, status, created_at
                FROM result_export_logs";

        if ($method === 'manual' || $method === '3g') {
            $sql .= " WHERE method = ?";
            $params[] = $method;
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $response->getBody()->write(json_encode($logs));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function exportJson(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $cityId = $this->getSetting($db, 'city_id');
        $query = $request->getQueryParams();
        $download = (string)($query['download'] ?? '0') === '1';
        $encrypted = (string)($query['encrypted'] ?? '0') === '1';
        $method = $this->getExportMethod($request);

        if (!$cityId) {
            $response->getBody()->write(json_encode(['message' => 'Machine not configured']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        if ($this->isExportLocked($db)) {
            $lockedMethod = $this->getSetting($db, 'export_method') ?? 'manual';
            $response->getBody()->write(json_encode([
                'message' => "Export already completed via {$lockedMethod}."
            ]));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        $rows = $this->fetchExportRows($db);
        $expectedVotes = count($rows);

        $map = [];
        foreach ($rows as $row) {
            $num = $row['ballot_number'];
            if (!isset($map[$num])) {
                $map[$num] = [];
            }
            $map[$num][] = intval($row['candidate_id']);
        }

        $results = [];
        foreach ($map as $ballotNumber => $choices) {
            $results[] = [
                'ballot_number' => $ballotNumber,
                'choices' => $choices
            ];
        }

        try {
            $exportKey = $this->createExportKey();
            $payload = [
                'city_id' => intval($cityId),
                'export_key' => $exportKey,
                'expected_votes' => $expectedVotes,
                'results' => $results
            ];

            $out = $payload;
            if ($encrypted) {
                $out = Crypto::encryptJson($payload, Crypto::AAD_RESULTS_V1);
            }

            $json = json_encode($out);
            if ($json === false) {
                $response->getBody()->write(json_encode(['message' => 'Unable to encode JSON']));
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }

            $this->setSetting($db, 'export_locked', '1');
            $this->setSetting($db, 'export_method', $method);
            $this->setSetting($db, 'export_key', $exportKey);
            $this->setSetting($db, 'exported_at', date('c'));
            $this->setSetting($db, 'voting_closed', '1');
            $this->markTransmitted($db);

            $status = $method === '3g' ? 'sent' : 'prepared';
            $this->insertExportLog($db, $exportKey, $expectedVotes, $expectedVotes, $method, $status);

            $response->getBody()->write($json);
            $response = $response->withHeader('Content-Type', 'application/json');
            if ($download) {
                $filename = $encrypted
                    ? 'results_' . intval($cityId) . '.enc.json'
                    : 'results_' . intval($cityId) . '.json';
                $response = $response->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            }

            return $response;
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['message' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function exportCsv(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $cityId = $this->getSetting($db, 'city_id');
        $method = $this->getExportMethod($request);

        if (!$cityId) {
            $response->getBody()->write(json_encode(['message' => 'Machine not configured']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        if ($this->isExportLocked($db)) {
            $lockedMethod = $this->getSetting($db, 'export_method') ?? 'manual';
            $response->getBody()->write(json_encode([
                'message' => "Export already completed via {$lockedMethod}."
            ]));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        $rows = $this->fetchExportRows($db);
        $expectedVotes = count($rows);
        $exportKey = $this->createExportKey();
        $this->setSetting($db, 'export_locked', '1');
        $this->setSetting($db, 'export_method', $method);
        $this->setSetting($db, 'export_key', $exportKey);
        $this->setSetting($db, 'exported_at', date('c'));
        $this->setSetting($db, 'voting_closed', '1');
        $this->markTransmitted($db);

        $status = $method === '3g' ? 'sent' : 'prepared';
        $this->insertExportLog($db, $exportKey, $expectedVotes, $expectedVotes, $method, $status);

        $stream = fopen('php://memory', 'w+');
        fputcsv($stream, ['TYPE', 'BALLOT_NUMBER', 'CANDIDATE_ID']);
        fputcsv($stream, ['EXPORT_KEY', $exportKey]);
        fputcsv($stream, ['CITY', $cityId]);

        foreach ($rows as $row) {
            fputcsv($stream, [
                'RESULT',
                $row['ballot_number'],
                $row['candidate_id']
            ]);
        }

        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        $response->getBody()->write($csvContent);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="results_' . $cityId . '.csv"');
    }

    public function printReturnPdf(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $cityId = $this->getSetting($db, 'city_id');
        $cityName = $this->getSetting($db, 'city_name') ?? 'Unknown City';

        if (!$cityId) {
            $response->getBody()->write(json_encode(['message' => 'Machine not configured']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $db->query("SELECT id, title, max_votes FROM positions ORDER BY id ASC");
        $positions = $stmt->fetchAll();

        $html = '<html><style>
            @page { margin: 16mm; }
            body { font-family: Arial, Helvetica, sans-serif; color: #000; }
            h1 { font-size: 20px; margin: 0 0 6px; }
            h2 { font-size: 14px; margin: 20px 0 8px; }
            .meta { font-size: 11px; color: #444; margin-bottom: 12px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            th, td { border: 1px solid #000; padding: 6px 8px; font-size: 11px; }
            th { background: #f2f2f2; text-align: left; }
            .footer { margin-top: 18px; font-size: 10px; text-align: right; color: #444; }
        </style><body>';

        $html .= '<h1>Election Return</h1>';
        $html .= '<div class="meta">City: ' . htmlspecialchars($cityName) . ' | Generated: ' . date('Y-m-d H:i') . '</div>';

        foreach ($positions as $position) {
            $stmt = $db->prepare("SELECT c.id, c.name, c.party_alias, COUNT(v.id) as vote_count
                FROM candidates c
                LEFT JOIN local_votes v ON v.candidate_id = c.id
                WHERE c.position_id = ?
                GROUP BY c.id
                ORDER BY vote_count DESC, c.name ASC");
            $stmt->execute([intval($position['id'])]);
            $rows = $stmt->fetchAll();

            $html .= '<h2>' . htmlspecialchars($position['title']) . '</h2>';
            $html .= '<table><thead><tr><th>Candidate</th><th>Party</th><th>Votes</th></tr></thead><tbody>';
            foreach ($rows as $row) {
                $html .= '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['party_alias']) . '</td><td>' . intval($row['vote_count']) . '</td></tr>';
            }
            if (count($rows) === 0) {
                $html .= '<tr><td colspan="3">No candidates.</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '<div class="footer">Voterra Election Return</div>';
        $html .= '</body></html>';

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('election_return_' . intval($cityId) . '.pdf', ['Attachment' => false]);

        return $response;
    }
}
