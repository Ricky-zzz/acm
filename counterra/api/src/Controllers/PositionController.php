<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class PositionController {

    public function getAll(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        
        // Join with cities to get the city name
        $sql = "SELECT p.*, c.name as city_name 
                FROM positions p 
                JOIN cities c ON p.city_id = c.id 
                ORDER BY c.name ASC, p.id ASC";
        
        $stmt = $db->query($sql);
        $positions = $stmt->fetchAll();

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $positions
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response) {
        try {
            $db = (new Database())->getConnection();
            $data = $request->getParsedBody();

            // Validate input
            if (!isset($data['city_id']) || !isset($data['title']) || !isset($data['max_votes'])) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $sql = "INSERT INTO positions (city_id, title, max_votes) 
                    VALUES (?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                intval($data['city_id']), 
                $data['title'], 
                intval($data['max_votes'])
            ]);

            if (!$result) {
                throw new \Exception('Failed to insert position');
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Position created successfully'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function update(Request $request, Response $response, array $args) {
        try {
            $id = intval($args['id']);
            $data = $request->getParsedBody();
            $db = (new Database())->getConnection();

            // Validate input
            if (!isset($data['city_id']) || !isset($data['title']) || !isset($data['max_votes'])) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $sql = "UPDATE positions SET city_id = ?, title = ?, max_votes = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                intval($data['city_id']), 
                $data['title'], 
                intval($data['max_votes']),
                $id
            ]);

            if (!$result) {
                throw new \Exception('Failed to update position');
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Position updated successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function delete(Request $request, Response $response, array $args) {
        try {
            $id = intval($args['id']);
            $db = (new Database())->getConnection();

            $stmt = $db->prepare("DELETE FROM positions WHERE id = ?");
            $result = $stmt->execute([$id]);

            if (!$result) {
                throw new \Exception('Failed to delete position');
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Position deleted successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
