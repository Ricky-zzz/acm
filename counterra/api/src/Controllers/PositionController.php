<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ServerRequestInterface as Request;
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

        $response->getBody()->write(json_encode($positions));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getOne(Request $request, Response $response, array $args) {
        $id = intval($args['id']);
        $db = (new Database())->getConnection();

        $sql = "SELECT p.*, c.name as city_name 
            FROM positions p 
            JOIN cities c ON p.city_id = c.id 
            WHERE p.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $position = $stmt->fetch();

        if (!$position) {
            $response->getBody()->write(json_encode([
                'message' => 'Position not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($position));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response) {
        try {
            $db = (new Database())->getConnection();
            $data = $request->getParsedBody();

            if (!is_array($data)) {
                $response->getBody()->write(json_encode([
                    'message' => 'Invalid request body'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Validate input
            $errors = [];
            if (!isset($data['city_id'])) {
                $errors['city_id'] = 'City is required';
            }
            if (!isset($data['title']) || trim((string)$data['title']) === '') {
                $errors['title'] = 'Title is required';
            }
            if (!isset($data['max_votes'])) {
                $errors['max_votes'] = 'Max votes is required';
            }
            if ($errors) {
                $response->getBody()->write(json_encode([
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
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

            $newId = $db->lastInsertId();

                $sql = "SELECT p.*, c.name as city_name 
                    FROM positions p 
                    JOIN cities c ON p.city_id = c.id 
                    WHERE p.id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([intval($newId)]);
            $created = $stmt->fetch();

            $response->getBody()->write(json_encode($created ?: [
                'id' => intval($newId),
                'city_id' => intval($data['city_id']),
                'title' => $data['title'],
                'max_votes' => intval($data['max_votes'])
            ]));

            return $response
                ->withStatus(201)
                ->withHeader('Location', '/acm/counterra/api/positions/' . $newId)
                ->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
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

            if (!is_array($data)) {
                $response->getBody()->write(json_encode([
                    'message' => 'Invalid request body'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Validate input
            $errors = [];
            if (!isset($data['city_id'])) {
                $errors['city_id'] = 'City is required';
            }
            if (!isset($data['title']) || trim((string)$data['title']) === '') {
                $errors['title'] = 'Title is required';
            }
            if (!isset($data['max_votes'])) {
                $errors['max_votes'] = 'Max votes is required';
            }
            if ($errors) {
                $response->getBody()->write(json_encode([
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
            }

            $sql = "UPDATE positions SET city_id = ?, title = ?, max_votes = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                intval($data['city_id']), 
                $data['title'], 
                intval($data['max_votes']),
                $id
            ]);

                $sql = "SELECT p.*, c.name as city_name 
                    FROM positions p 
                    JOIN cities c ON p.city_id = c.id 
                    WHERE p.id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $position = $stmt->fetch();

            if (!$position) {
                $response->getBody()->write(json_encode([
                    'message' => 'Position not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($position));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
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
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode([
                    'message' => 'Position not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            return $response->withStatus(204);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
