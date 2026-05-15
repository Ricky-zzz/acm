<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VoteController {

    public function cast(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $db = (new Database())->getConnection();

        if (!is_array($data)) {
            $response->getBody()->write(json_encode(['message' => 'Invalid request body']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $ballotNumber = (string)($data['ballot_number'] ?? '');
        $choices = $data['choices'] ?? [];

        if ($ballotNumber === '' || !is_array($choices) || count($choices) === 0) {
            $response->getBody()->write(json_encode(['message' => 'Ballot number and choices are required']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $choices = array_values(array_unique(array_map('intval', $choices)));

        $stmt = $db->prepare("SELECT is_used FROM authorized_ballots WHERE ballot_number = ?");
        $stmt->execute([$ballotNumber]);
        $ballot = $stmt->fetch();

        if (!$ballot) {
            $response->getBody()->write(json_encode(['message' => 'Invalid ballot']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if (intval($ballot['is_used']) === 1) {
            $response->getBody()->write(json_encode(['message' => 'Ballot already used']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }

        $placeholders = implode(',', array_fill(0, count($choices), '?'));
        $stmt = $db->prepare("SELECT id, position_id FROM candidates WHERE id IN ($placeholders)");
        $stmt->execute($choices);
        $candidateRows = $stmt->fetchAll();

        if (count($candidateRows) !== count($choices)) {
            $response->getBody()->write(json_encode(['message' => 'Invalid candidate selection']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $counts = [];
        $positionIds = [];
        foreach ($candidateRows as $row) {
            $posId = intval($row['position_id']);
            $counts[$posId] = ($counts[$posId] ?? 0) + 1;
            $positionIds[$posId] = true;
        }

        $posPlaceholders = implode(',', array_fill(0, count($positionIds), '?'));
        $stmt = $db->prepare("SELECT id, max_votes FROM positions WHERE id IN ($posPlaceholders)");
        $stmt->execute(array_keys($positionIds));
        $positionRows = $stmt->fetchAll();

        $maxMap = [];
        foreach ($positionRows as $row) {
            $maxMap[intval($row['id'])] = intval($row['max_votes']);
        }

        foreach ($counts as $posId => $count) {
            $maxVotes = $maxMap[$posId] ?? 0;
            if ($maxVotes === 0 || $count > $maxVotes) {
                $response->getBody()->write(json_encode(['message' => 'Too many selections for a position']));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
            }
        }

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("INSERT INTO local_votes (ballot_number, candidate_id) VALUES (?, ?)");
            foreach ($choices as $candidateId) {
                $stmt->execute([$ballotNumber, $candidateId]);
            }

            $stmt = $db->prepare("UPDATE authorized_ballots SET is_used = 1 WHERE ballot_number = ?");
            $stmt->execute([$ballotNumber]);

            $db->commit();
            $response->getBody()->write(json_encode(['status' => 'success']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $db->rollBack();
            $response->getBody()->write(json_encode(['message' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
