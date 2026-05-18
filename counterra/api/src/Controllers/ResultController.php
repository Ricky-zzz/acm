<?php

namespace App\Controllers;

use App\Crypto;
use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResultController {

    private function getImportMethod(Request $request): string {
        $params = $request->getQueryParams();
        $method = strtolower((string)($params['method'] ?? 'manual'));
        return $method === '3g' ? '3g' : 'manual';
    }

    private function importKeyExists($db, string $importKey): bool {
        $stmt = $db->prepare("SELECT id FROM result_import_logs WHERE import_key = ?");
        $stmt->execute([$importKey]);
        return (bool)$stmt->fetchColumn();
    }

    private function insertImportLog($db, int $cityId, string $importKey, int $expectedVotes, string $method, string $note): int {
        $stmt = $db->prepare("INSERT INTO result_import_logs (city_id, import_key, expected_votes, received_votes, method, status, note) VALUES (?, ?, ?, 0, ?, 'pending', ?)");
        $stmt->execute([$cityId, $importKey, $expectedVotes, $method, $note]);
        return intval($db->lastInsertId());
    }

    private function updateImportLog($db, int $logId, int $receivedVotes, string $note): void {
        $stmt = $db->prepare("UPDATE result_import_logs SET received_votes = ?, note = ? WHERE id = ?");
        $stmt->execute([$receivedVotes, $note, $logId]);
    }

    public function import(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $db = (new Database())->getConnection();

        if (!is_array($data)) {
            $response->getBody()->write(json_encode([
                'message' => 'Invalid request body'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (!Crypto::isEnvelope($data)) {
            $response->getBody()->write(json_encode([
                'message' => 'Encrypted results payload required'
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        try {
            $data = Crypto::decryptEnvelopeToArray($data, Crypto::AAD_RESULTS_V1);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode([
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $cityId = isset($data['city_id']) ? intval($data['city_id']) : 0;
        $results = $data['results'] ?? [];
        $importKey = (string)($data['export_key'] ?? '');
        $expectedVotes = intval($data['expected_votes'] ?? 0);
        $method = $this->getImportMethod($request);

        if ($cityId <= 0 || !is_array($results)) {
            $response->getBody()->write(json_encode([
                'message' => 'Validation failed'
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        if ($importKey === '') {
            $importKey = bin2hex(random_bytes(16));
        }

        if ($this->importKeyExists($db, $importKey)) {
            $response->getBody()->write(json_encode([
                'message' => 'Duplicate import key'
            ]));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        if ($expectedVotes <= 0) {
            $expectedVotes = 0;
            foreach ($results as $item) {
                $choices = $item['choices'] ?? [];
                if (is_array($choices)) {
                    $expectedVotes += count($choices);
                }
            }
        }

        $processedCount = 0;
        $receivedVotes = 0;
        $errors = [];

        $db->beginTransaction();

        try {
            $logId = $this->insertImportLog($db, $cityId, $importKey, $expectedVotes, $method, 'smooth');

            foreach ($results as $item) {
                $ballotNum = $item['ballot_number'] ?? '';
                $choices = $item['choices'] ?? [];

                if ($ballotNum === '' || !is_array($choices)) {
                    $errors[] = 'Invalid item format.';
                    continue;
                }

                $stmt = $db->prepare("SELECT id, status FROM ballots WHERE ballot_number = ? AND city_id = ?");
                $stmt->execute([$ballotNum, $cityId]);
                $ballot = $stmt->fetch();

                if (!$ballot) {
                    $errors[] = "Ballot $ballotNum: Invalid ID";
                    continue;
                }

                if ($ballot['status'] === 'used') {
                    $errors[] = "Ballot $ballotNum: Already used";
                    continue;
                }

                foreach ($choices as $candidateId) {
                    $stmt = $db->prepare("INSERT INTO votes (ballot_id, candidate_id) VALUES (?, ?)");
                    $stmt->execute([$ballot['id'], intval($candidateId)]);
                    $receivedVotes++;
                }

                $stmt = $db->prepare("UPDATE ballots SET status = 'used' WHERE id = ?");
                $stmt->execute([$ballot['id']]);

                $processedCount++;
            }

            $note = count($errors) > 0 ? 'interrupted' : 'smooth';
            $this->updateImportLog($db, $logId, $receivedVotes, $note);

            $db->commit();

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'processed' => $processedCount,
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $db->rollBack();
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function importCsv(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $uploadedFiles = $request->getUploadedFiles();
        $method = $this->getImportMethod($request);

        if (!isset($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode([
                'message' => 'CSV file is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $file = $uploadedFiles['file'];
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode([
                'message' => 'Upload failed'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $handle = $file->getStream()->detach();
        if ($handle === null) {
            $response->getBody()->write(json_encode([
                'message' => 'Unable to read file'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $cityId = 0;
        $map = [];
        $importKey = '';

        while (($row = fgetcsv($handle)) !== false) {
            $type = strtoupper(trim($row[0] ?? ''));
            if ($type === '' || $type === 'TYPE') {
                continue;
            }

            if ($type === 'CITY') {
                $cityId = intval($row[1] ?? 0);
                continue;
            }

            if ($type === 'EXPORT_KEY') {
                $importKey = (string)($row[1] ?? '');
                continue;
            }

            if ($type === 'RESULT') {
                $ballotNum = (string)($row[1] ?? '');
                $candidateId = intval($row[2] ?? 0);
                if ($ballotNum === '' || $candidateId === 0) {
                    continue;
                }
                if (!isset($map[$ballotNum])) {
                    $map[$ballotNum] = [];
                }
                $map[$ballotNum][] = $candidateId;
            }
        }

        fclose($handle);

        if ($cityId <= 0) {
            $response->getBody()->write(json_encode([
                'message' => 'City row missing in CSV'
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        if ($importKey === '') {
            $importKey = bin2hex(random_bytes(16));
        }

        if ($this->importKeyExists($db, $importKey)) {
            $response->getBody()->write(json_encode([
                'message' => 'Duplicate import key'
            ]));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        $results = [];
        foreach ($map as $ballotNum => $choices) {
            $results[] = [
                'ballot_number' => $ballotNum,
                'choices' => $choices
            ];
        }

        $processedCount = 0;
        $receivedVotes = 0;
        $errors = [];
        $expectedVotes = 0;
        foreach ($results as $item) {
            $choices = $item['choices'] ?? [];
            if (is_array($choices)) {
                $expectedVotes += count($choices);
            }
        }

        $db->beginTransaction();

        try {
            $logId = $this->insertImportLog($db, $cityId, $importKey, $expectedVotes, $method, 'smooth');

            foreach ($results as $item) {
                $ballotNum = $item['ballot_number'] ?? '';
                $choices = $item['choices'] ?? [];

                if ($ballotNum === '' || !is_array($choices)) {
                    $errors[] = 'Invalid item format.';
                    continue;
                }

                $stmt = $db->prepare("SELECT id, status FROM ballots WHERE ballot_number = ? AND city_id = ?");
                $stmt->execute([$ballotNum, $cityId]);
                $ballot = $stmt->fetch();

                if (!$ballot) {
                    $errors[] = "Ballot $ballotNum: Invalid ID";
                    continue;
                }

                if ($ballot['status'] === 'used') {
                    $errors[] = "Ballot $ballotNum: Already used";
                    continue;
                }

                foreach ($choices as $candidateId) {
                    $stmt = $db->prepare("INSERT INTO votes (ballot_id, candidate_id) VALUES (?, ?)");
                    $stmt->execute([$ballot['id'], intval($candidateId)]);
                    $receivedVotes++;
                }

                $stmt = $db->prepare("UPDATE ballots SET status = 'used' WHERE id = ?");
                $stmt->execute([$ballot['id']]);

                $processedCount++;
            }

            $note = count($errors) > 0 ? 'interrupted' : 'smooth';
            $this->updateImportLog($db, $logId, $receivedVotes, $note);

            $db->commit();

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'processed' => $processedCount,
                'errors' => $errors
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $db->rollBack();
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getTally(Request $request, Response $response, array $args) {
        $cityId = intval($args['city_id']);
        $db = (new Database())->getConnection();

        $sql = "SELECT c.id as candidate_id,
                       c.name as candidate_name,
                       p.title as position_title,
                       par.alias as party_alias,
                       COUNT(v.id) as vote_count
                FROM candidates c
                JOIN positions p ON c.position_id = p.id
                JOIN parties par ON c.party_id = par.id
                LEFT JOIN votes v ON c.id = v.candidate_id
                WHERE p.city_id = ?
                GROUP BY c.id
                ORDER BY p.id ASC, vote_count DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$cityId]);
        $tally = $stmt->fetchAll();

        $response->getBody()->write(json_encode($tally));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getImportLogs(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $query = $request->getQueryParams();
        $method = strtolower((string)($query['method'] ?? ''));
        $params = [];

        $sql = "SELECT l.id, l.city_id, c.name as city_name, l.import_key, l.expected_votes, l.received_votes, l.method, l.status, l.note, l.created_at
                FROM result_import_logs l
                JOIN cities c ON l.city_id = c.id";

        if ($method === 'manual' || $method === '3g') {
            $sql .= " WHERE l.method = ?";
            $params[] = $method;
        }

        $sql .= " ORDER BY l.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $response->getBody()->write(json_encode($logs));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updateImportStatus(Request $request, Response $response, array $args) {
        $logId = intval($args['id'] ?? 0);
        $data = $request->getParsedBody();
        $status = strtolower((string)($data['status'] ?? ''));

        if ($logId <= 0 || ($status !== 'accepted' && $status !== 'rejected')) {
            $response->getBody()->write(json_encode(['message' => 'Invalid status update']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("UPDATE result_import_logs SET status = ? WHERE id = ?");
        $stmt->execute([$status, $logId]);

        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
