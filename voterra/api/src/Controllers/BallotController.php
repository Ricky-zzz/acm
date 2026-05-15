<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BallotController {

    public function validate(Request $request, Response $response) {
        $data = $request->getParsedBody();
        $db = (new Database())->getConnection();

        if (!is_array($data) || empty($data['ballot_number'])) {
            $response->getBody()->write(json_encode(['status' => 'invalid', 'message' => 'Ballot number is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $ballotNumber = (string)$data['ballot_number'];

        $stmt = $db->prepare("SELECT is_used FROM authorized_ballots WHERE ballot_number = ?");
        $stmt->execute([$ballotNumber]);
        $ballot = $stmt->fetch();

        if (!$ballot) {
            $response->getBody()->write(json_encode(['status' => 'invalid']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        if (intval($ballot['is_used']) === 1) {
            $response->getBody()->write(json_encode(['status' => 'used']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['status' => 'valid']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
