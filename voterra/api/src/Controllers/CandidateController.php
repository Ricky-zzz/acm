<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CandidateController {

    public function getAll(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $sql = "SELECT c.*, p.title as position_title
                FROM candidates c
                JOIN positions p ON c.position_id = p.id
                ORDER BY p.id ASC, c.name ASC";
        $stmt = $db->query($sql);
        $candidates = $stmt->fetchAll();

        $response->getBody()->write(json_encode($candidates));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
