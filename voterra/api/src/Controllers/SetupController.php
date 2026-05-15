<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SetupController {

    private function resetMachine($db): void {
        $db->exec("DELETE FROM local_votes");
        $db->exec("DELETE FROM authorized_ballots");
        $db->exec("DELETE FROM candidates");
        $db->exec("DELETE FROM positions");
        $db->exec("DELETE FROM settings");
    }

    private function upsertSetting($db, string $key, string $value): void {
        $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        $stmt->execute([$key, $value]);
    }

    public function status(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $stmt = $db->query("SELECT `key`, `value` FROM settings");
        $settings = $stmt->fetchAll();

        $data = [];
        foreach ($settings as $row) {
            $data[$row['key']] = $row['value'];
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function importJson(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $db = (new Database())->getConnection();

        if (!is_array($data)) {
            $response->getBody()->write(json_encode(['message' => 'Invalid request body']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $city = $data['city'] ?? null;
        $positions = $data['positions'] ?? [];
        $candidates = $data['candidates'] ?? [];
        $validBallots = $data['valid_ballots'] ?? [];

        if (!is_array($city) || empty($city['id']) || empty($city['name'])) {
            $response->getBody()->write(json_encode(['message' => 'City is required']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        if (!is_array($positions) || !is_array($candidates) || !is_array($validBallots)) {
            $response->getBody()->write(json_encode(['message' => 'Invalid setup payload']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $db->beginTransaction();

        try {
            $this->resetMachine($db);
            $this->upsertSetting($db, 'city_id', (string)$city['id']);
            $this->upsertSetting($db, 'city_name', (string)$city['name']);

            $stmt = $db->prepare("INSERT INTO positions (id, title, max_votes) VALUES (?, ?, ?)");
            foreach ($positions as $pos) {
                $stmt->execute([
                    intval($pos['id']),
                    $pos['title'],
                    intval($pos['max_votes'])
                ]);
            }

            $stmt = $db->prepare("INSERT INTO candidates (id, position_id, name, party_alias) VALUES (?, ?, ?, ?)");
            foreach ($candidates as $can) {
                $stmt->execute([
                    intval($can['id']),
                    intval($can['position_id']),
                    $can['name'],
                    $can['party_alias'] ?? ''
                ]);
            }

            $stmt = $db->prepare("INSERT INTO authorized_ballots (ballot_number) VALUES (?)");
            foreach ($validBallots as $num) {
                $stmt->execute([$num]);
            }

            $db->commit();
            $response->getBody()->write(json_encode(['status' => 'Machine Initialized Successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $db->rollBack();
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function importCsv(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode(['message' => 'CSV file is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $file = $uploadedFiles['file'];
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['message' => 'Upload failed']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $handle = $file->getStream()->detach();
        if ($handle === null) {
            $response->getBody()->write(json_encode(['message' => 'Unable to read file']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $city = null;
        $positions = [];
        $candidates = [];
        $ballots = [];

        while (($row = fgetcsv($handle)) !== false) {
            $type = strtoupper(trim($row[0] ?? ''));
            if ($type === '' || $type === 'TYPE') {
                continue;
            }

            if ($type === 'CITY') {
                $city = [
                    'id' => intval($row[1] ?? 0),
                    'name' => (string)($row[2] ?? '')
                ];
                continue;
            }

            if ($type === 'POSITION') {
                $positions[] = [
                    'id' => intval($row[1] ?? 0),
                    'title' => (string)($row[2] ?? ''),
                    'max_votes' => intval($row[3] ?? 0)
                ];
                continue;
            }

            if ($type === 'CANDIDATE') {
                $candidates[] = [
                    'id' => intval($row[1] ?? 0),
                    'position_id' => intval($row[2] ?? 0),
                    'name' => (string)($row[3] ?? ''),
                    'party_alias' => (string)($row[4] ?? '')
                ];
                continue;
            }

            if ($type === 'BALLOT') {
                $ballots[] = (string)($row[1] ?? '');
            }
        }

        fclose($handle);

        if (!$city || empty($city['id']) || empty($city['name'])) {
            $response->getBody()->write(json_encode(['message' => 'City row missing in CSV']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $db->beginTransaction();

        try {
            $this->resetMachine($db);
            $this->upsertSetting($db, 'city_id', (string)$city['id']);
            $this->upsertSetting($db, 'city_name', (string)$city['name']);

            $stmt = $db->prepare("INSERT INTO positions (id, title, max_votes) VALUES (?, ?, ?)");
            foreach ($positions as $pos) {
                $stmt->execute([
                    intval($pos['id']),
                    $pos['title'],
                    intval($pos['max_votes'])
                ]);
            }

            $stmt = $db->prepare("INSERT INTO candidates (id, position_id, name, party_alias) VALUES (?, ?, ?, ?)");
            foreach ($candidates as $can) {
                $stmt->execute([
                    intval($can['id']),
                    intval($can['position_id']),
                    $can['name'],
                    $can['party_alias']
                ]);
            }

            $stmt = $db->prepare("INSERT INTO authorized_ballots (ballot_number) VALUES (?)");
            foreach ($ballots as $num) {
                if ($num === '') {
                    continue;
                }
                $stmt->execute([$num]);
            }

            $db->commit();
            $response->getBody()->write(json_encode(['status' => 'Machine Initialized Successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $db->rollBack();
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
