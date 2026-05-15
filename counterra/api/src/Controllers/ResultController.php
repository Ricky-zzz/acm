<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResultController {

    public function import(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $db = (new Database())->getConnection();

        if (!is_array($data)) {
            $response->getBody()->write(json_encode([
                'message' => 'Invalid request body'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $cityId = isset($data['city_id']) ? intval($data['city_id']) : 0;
        $results = $data['results'] ?? [];

        if ($cityId <= 0 || !is_array($results)) {
            $response->getBody()->write(json_encode([
                'message' => 'Validation failed'
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $processedCount = 0;
        $errors = [];

        $db->beginTransaction();

        try {
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
                }

                $stmt = $db->prepare("UPDATE ballots SET status = 'used' WHERE id = ?");
                $stmt->execute([$ballot['id']]);

                $processedCount++;
            }

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

        while (($row = fgetcsv($handle)) !== false) {
            $type = strtoupper(trim($row[0] ?? ''));
            if ($type === '' || $type === 'TYPE') {
                continue;
            }

            if ($type === 'CITY') {
                $cityId = intval($row[1] ?? 0);
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

        $results = [];
        foreach ($map as $ballotNum => $choices) {
            $results[] = [
                'ballot_number' => $ballotNum,
                'choices' => $choices
            ];
        }

        $processedCount = 0;
        $errors = [];

        $db->beginTransaction();

        try {
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
                }

                $stmt = $db->prepare("UPDATE ballots SET status = 'used' WHERE id = ?");
                $stmt->execute([$ballot['id']]);

                $processedCount++;
            }

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
}
