<?php

namespace App\Controllers;

use App\Database;
use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BallotController {

    public function getAll(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $query = $request->getQueryParams();

        $sql = "SELECT b.*, c.name as city_name
                FROM ballots b
                JOIN cities c ON b.city_id = c.id";

        $conditions = [];
        $params = [];

        if (!empty($query['status'])) {
            $conditions[] = "b.status = ?";
            $params[] = $query['status'];
        }

        if (!empty($query['city_id'])) {
            $conditions[] = "b.city_id = ?";
            $params[] = intval($query['city_id']);
        }

        if ($conditions) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY c.name ASC, b.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $ballots = $stmt->fetchAll();

        $response->getBody()->write(json_encode($ballots));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function generate(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            $db = (new Database())->getConnection();

            if (!is_array($data)) {
                $response->getBody()->write(json_encode([
                    'message' => 'Invalid request body'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $errors = [];
            if (!isset($data['city_id'])) {
                $errors['city_id'] = 'City is required';
            }
            if (!isset($data['quantity']) || intval($data['quantity']) <= 0) {
                $errors['quantity'] = 'Quantity must be greater than 0';
            }
            if ($errors) {
                $response->getBody()->write(json_encode([
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
            }

            $cityId = intval($data['city_id']);
            $quantity = intval($data['quantity']);

            $stmt = $db->prepare("SELECT name FROM cities WHERE id = ?");
            $stmt->execute([$cityId]);
            $city = $stmt->fetch();

            if (!$city) {
                $response->getBody()->write(json_encode([
                    'message' => 'City not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $prefix = strtoupper(substr($city['name'], 0, 3));
            $randomPart = strtoupper(bin2hex(random_bytes(2)));

            $stmt = $db->prepare("INSERT INTO ballots (city_id, ballot_number) VALUES (?, ?)");
            for ($i = 1; $i <= $quantity; $i++) {
                $sequence = str_pad((string)$i, 4, '0', STR_PAD_LEFT);
                $ballotNumber = $prefix . '-' . $randomPart . '-' . $sequence;
                $stmt->execute([$cityId, $ballotNumber]);
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'count' => $quantity
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function printBallots(Request $request, Response $response, array $args) {
        $cityId = intval($args['id']);
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
        $stmt->execute([$cityId]);
        $city = $stmt->fetch();

        if (!$city) {
            $response->getBody()->write(json_encode([
                'message' => 'City not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $db->prepare("SELECT * FROM positions WHERE city_id = ?");
        $stmt->execute([$cityId]);
        $positions = $stmt->fetchAll();

        foreach ($positions as &$pos) {
            $stmt = $db->prepare("SELECT c.*, par.alias as party_alias
                FROM candidates c
                JOIN parties par ON c.party_id = par.id
                WHERE c.position_id = ?");
            $stmt->execute([$pos['id']]);
            $pos['candidates'] = $stmt->fetchAll();
        }
        unset($pos);

        $stmt = $db->prepare("SELECT ballot_number FROM ballots WHERE city_id = ? AND status = 'unused'");
        $stmt->execute([$cityId]);
        $ballots = $stmt->fetchAll();

        $html = '<html><style>
            @page { margin: 14mm; }
            body { font-family: Arial, Helvetica, sans-serif; color: #000; }
            .ballot { border: 2px solid #000; padding: 18px 20px 22px; margin-bottom: 24px; page-break-after: always; }
            .topline { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 10px; }
            .seal { border: 2px solid #000; padding: 8px 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; flex: 1; }
            .header h1 { margin: 0; font-size: 28px; letter-spacing: 1px; }
            .header h3 { margin: 8px 0 0; font-size: 15px; font-weight: 700; }
            .pos-title { background: #000; color: #fff; padding: 5px; margin-top: 15px; font-size: 14px; }
            .candidate { margin: 5px 0; font-size: 12px; display: flex; align-items: center; }
            .bubble { border: 1px solid #000; border-radius: 50%; width: 14px; height: 14px; display: inline-block; margin-right: 10px; flex: 0 0 14px; }
            .footer { margin-top: 20px; border-top: 1px dashed #000; padding-top: 10px; font-size: 10px; text-align: center; }
        </style><body>';

        foreach ($ballots as $b) {
            $html .= '<div class="ballot">';
            $html .= '<div class="topline">';
            $html .= '<div class="seal">Official Use<br>Election Ballot</div>';
            $html .= '<div class="header"><h1>OFFICIAL BALLOT</h1><h3>City of ' . $city['name'] . '</h3></div>';
            $html .= '<div class="seal">Ballot No.<br>' . htmlspecialchars($b['ballot_number']) . '</div>';
            $html .= '</div>';

            foreach ($positions as $p) {
                $html .= '<div class="pos-title">' . strtoupper($p['title']) . ' (Select ' . $p['max_votes'] . ')</div>';
                foreach ($p['candidates'] as $can) {
                    $html .= '<div class="candidate"><span class="bubble"></span> ' . $can['name'] . ' (' . $can['party_alias'] . ')</div>';
                }
            }

            $html .= '<div class="footer">Ballot ID: <strong>' . htmlspecialchars($b['ballot_number']) . '</strong></div>';
            $html .= '</div>';
        }
        $html .= '</body></html>';

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('ballots_' . $city['name'] . '.pdf', ['Attachment' => false]);

        return $response;
    }

    public function exportSetupJson(Request $request, Response $response, array $args) {
        $cityId = intval($args['id']);
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
        $stmt->execute([$cityId]);
        $city = $stmt->fetch();

        if (!$city) {
            $response->getBody()->write(json_encode([
                'message' => 'City not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $db->prepare("SELECT id, title, max_votes FROM positions WHERE city_id = ? ORDER BY id ASC");
        $stmt->execute([$cityId]);
        $positions = $stmt->fetchAll();

        $sql = "SELECT c.id, c.position_id, c.name, par.alias as party_alias
                FROM candidates c
                JOIN parties par ON c.party_id = par.id
                JOIN positions p ON c.position_id = p.id
                WHERE p.city_id = ?
                ORDER BY p.id ASC, c.name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$cityId]);
        $candidates = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT ballot_number FROM ballots WHERE city_id = ? AND status = 'unused'");
        $stmt->execute([$cityId]);
        $ballots = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $payload = [
            'city' => $city,
            'positions' => $positions,
            'candidates' => $candidates,
            'valid_ballots' => $ballots
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function exportSetupCsv(Request $request, Response $response, array $args) {
        $cityId = intval($args['id']);
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
        $stmt->execute([$cityId]);
        $city = $stmt->fetch();

        if (!$city) {
            $response->getBody()->write(json_encode([
                'message' => 'City not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $db->prepare("SELECT id, title, max_votes FROM positions WHERE city_id = ? ORDER BY id ASC");
        $stmt->execute([$cityId]);
        $positions = $stmt->fetchAll();

        $sql = "SELECT c.id, c.position_id, c.name, par.alias as party_alias
                FROM candidates c
                JOIN parties par ON c.party_id = par.id
                JOIN positions p ON c.position_id = p.id
                WHERE p.city_id = ?
                ORDER BY p.id ASC, c.name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$cityId]);
        $candidates = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT ballot_number FROM ballots WHERE city_id = ? AND status = 'unused'");
        $stmt->execute([$cityId]);
        $ballots = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $stream = fopen('php://memory', 'w+');
        fputcsv($stream, ['TYPE', 'ID', 'NAME', 'POSITION_ID', 'TITLE', 'MAX_VOTES', 'PARTY_ALIAS', 'BALLOT_NUMBER'], ',', '"', '\\');
        fputcsv($stream, ['CITY', $city['id'], $city['name'], $city['councilor_limit'] ?? 0], ',', '"', '\\');

        foreach ($positions as $pos) {
            fputcsv($stream, ['POSITION', $pos['id'], $pos['title'], $pos['max_votes']], ',', '"', '\\');
        }

        foreach ($candidates as $can) {
            fputcsv($stream, ['CANDIDATE', $can['id'], $can['position_id'], $can['name'], $can['party_alias']], ',', '"', '\\');
        }

        foreach ($ballots as $num) {
            fputcsv($stream, ['BALLOT', $num], ',', '"', '\\');
        }

        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        $response->getBody()->write($csvContent);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="setup_city_' . $cityId . '.csv"');
    }
}
