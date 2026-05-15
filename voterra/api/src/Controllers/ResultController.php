<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResultController {

    private function getSetting($db, string $key): ?string {
        $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string)$value : null;
    }

        private function getExportOptions(Request $request): array {
            $params = $request->getQueryParams();
            $scope = strtolower((string)($params['scope'] ?? 'untransmitted'));
            if ($scope !== 'all' && $scope !== 'untransmitted') {
                $scope = 'untransmitted';
            }

            $mark = (string)($params['mark'] ?? '0') === '1';
            if ($scope === 'all') {
                $mark = false;
            }

            return [$scope, $mark];
        }

        private function fetchExportRows($db, string $scope): array {
            $sql = "SELECT ballot_number, candidate_id FROM local_votes";
            if ($scope === 'untransmitted') {
                $sql .= " WHERE is_transmitted = 0";
            }
            $sql .= " ORDER BY ballot_number ASC";

            $stmt = $db->query($sql);
            return $stmt->fetchAll();
        }

        private function markTransmitted($db): void {
            $db->exec("UPDATE local_votes SET is_transmitted = 1 WHERE is_transmitted = 0");
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

    public function exportJson(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $cityId = $this->getSetting($db, 'city_id');

        if (!$cityId) {
            $response->getBody()->write(json_encode(['message' => 'Machine not configured']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

            [$scope, $mark] = $this->getExportOptions($request);
            $rows = $this->fetchExportRows($db, $scope);

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

            if ($mark && !empty($rows)) {
                $this->markTransmitted($db);
            }

        $response->getBody()->write(json_encode([
            'city_id' => intval($cityId),
            'results' => $results
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function exportCsv(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $cityId = $this->getSetting($db, 'city_id');

        if (!$cityId) {
            $response->getBody()->write(json_encode(['message' => 'Machine not configured']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

            [$scope, $mark] = $this->getExportOptions($request);
            $rows = $this->fetchExportRows($db, $scope);

        $stream = fopen('php://memory', 'w+');
        fputcsv($stream, ['TYPE', 'BALLOT_NUMBER', 'CANDIDATE_ID']);
        fputcsv($stream, ['CITY', $cityId]);

        foreach ($rows as $row) {
            fputcsv($stream, [
                'RESULT',
                $row['ballot_number'],
                $row['candidate_id']
            ]);
        }

            if ($mark && !empty($rows)) {
                $this->markTransmitted($db);
            }

        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        $response->getBody()->write($csvContent);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="results_' . $cityId . '.csv"');
    }
}
