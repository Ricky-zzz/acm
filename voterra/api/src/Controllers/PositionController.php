<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PositionController {

    public function getAll(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $stmt = $db->query("SELECT * FROM positions ORDER BY id ASC");
        $positions = $stmt->fetchAll();

        $response->getBody()->write(json_encode($positions));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
