<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database;

class CityController {
    
    public function getAll(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $stmt = $db->query("SELECT * FROM cities");
        $cities = $stmt->fetchAll();
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'data' => $cities
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getFullSetup(Request $request, Response $response, array $args) {
        $city_id = $args['id'];
        $db = (new Database())->getConnection();

        // Get City
        $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
        $stmt->execute([$city_id]);
        $city = $stmt->fetch();

        // Get Positions and their Candidates
        $stmt = $db->prepare("SELECT * FROM positions WHERE city_id = ?");
        $stmt->execute([$city_id]);
        $positions = $stmt->fetchAll();

        foreach($positions as &$pos) {
            $stmt = $db->prepare("SELECT * FROM candidates WHERE position_id = ?");
            $stmt->execute([$pos['id']]);
            $pos['candidates'] = $stmt->fetchAll();
        }

        $data = [
            'city' => $city,
            'positions' => $positions
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            $db = (new Database())->getConnection();

            // Validate input
            if (!isset($data['name']) || !isset($data['councilor_limit'])) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $db->prepare("INSERT INTO cities (name, councilor_limit) VALUES (?, ?)");
            $result = $stmt->execute([
                $data['name'], 
                intval($data['councilor_limit'])
            ]);

            if (!$result) {
                throw new \Exception('Failed to insert city');
            }

            $newId = $db->lastInsertId();

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => [
                    'id' => $newId,
                    'name' => $data['name'],
                    'councilor_limit' => intval($data['councilor_limit'])
                ],
                'message' => 'City created successfully'
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
            if (!isset($data['name']) || !isset($data['councilor_limit'])) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $db->prepare("UPDATE cities SET name = ?, councilor_limit = ? WHERE id = ?");
            $result = $stmt->execute([
                $data['name'], 
                intval($data['councilor_limit']), 
                $id
            ]);

            if (!$result) {
                throw new \Exception('Failed to update city');
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'City updated successfully'
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

            $stmt = $db->prepare("DELETE FROM cities WHERE id = ?");
            $result = $stmt->execute([$id]);

            if (!$result) {
                throw new \Exception('Failed to delete city');
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'City deleted successfully'
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