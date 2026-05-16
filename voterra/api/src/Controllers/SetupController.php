<?php

namespace App\Controllers;

use App\Crypto;
use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SetupController {

    private function json(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    private function configuredCityId($db): ?string {
        $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute(['city_id']);
        $value = $stmt->fetchColumn();

        if ($value === false || $value === null || $value === '') {
            return null;
        }

        return (string)$value;
    }

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
            return $this->json($response, ['message' => 'Invalid request body'], 400);
        }

        if (!Crypto::isEnvelope($data)) {
            return $this->json($response, ['message' => 'Encrypted setup payload required'], 422);
        }

        try {
            $data = Crypto::decryptEnvelopeToArray($data, Crypto::AAD_SETUP_V1);
        } catch (\RuntimeException $e) {
            return $this->json($response, ['message' => 'Decryption failed: ' . $e->getMessage()], 400);
        }

        $city = $data['city'] ?? null;
        $positions = $data['positions'] ?? [];
        $candidates = $data['candidates'] ?? [];
        $validBallots = $data['valid_ballots'] ?? [];

        if (!is_array($city) || empty($city['id']) || empty($city['name'])) {
            return $this->json($response, ['message' => 'City is required'], 422);
        }

        if (!is_array($positions) || !is_array($candidates) || !is_array($validBallots)) {
            return $this->json($response, ['message' => 'Invalid setup payload'], 422);
        }

        $incomingCityId = (string)$city['id'];
        $currentCityId = $this->configuredCityId($db);

        if ($currentCityId !== null && $currentCityId !== $incomingCityId) {
            return $this->json($response, [
                'message' => 'City mismatch: machine configured for a different city. Use Cleanup -> Wipe to reconfigure.'
            ], 409);
        }

        if ($currentCityId !== null) {
            try {
                $stmt = $db->prepare("INSERT IGNORE INTO authorized_ballots (ballot_number) VALUES (?)");
                $added = 0;

                foreach ($validBallots as $num) {
                    if ((string)$num === '') {
                        continue;
                    }

                    $stmt->execute([(string)$num]);
                    $added += $stmt->rowCount();
                }

                return $this->json($response, [
                    'status' => 'Ballots imported successfully',
                    'message' => $added > 0
                        ? "Added {$added} new ballot(s) for the current city."
                        : 'No new ballots were added. They may already be authorized.'
                ]);
            } catch (\Exception $e) {
                return $this->json($response, ['status' => 'error', 'message' => $e->getMessage()], 500);
            }
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
            return $this->json($response, ['status' => 'Machine Initialized Successfully']);
        } catch (\Exception $e) {
            $db->rollBack();
            return $this->json($response, ['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function wipe(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $db->beginTransaction();

        try {
            $this->resetMachine($db);
            $db->commit();
            return $this->json($response, ['status' => 'Machine wiped successfully']);
        } catch (\Exception $e) {
            $db->rollBack();
            return $this->json($response, ['status' => 'error', 'message' => $e->getMessage()], 500);
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
