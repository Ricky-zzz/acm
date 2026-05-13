<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database;

class PartyController {
    
    public function getAll(Request $request, Response $response) {
        $db = (new Database())->getConnection();
        $stmt = $db->query("SELECT * FROM parties");
        $parties = $stmt->fetchAll();

        $response->getBody()->write(json_encode($parties));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getOne(Request $request, Response $response, array $args) {
        $party_id = intval($args['id']);
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("SELECT * FROM parties WHERE id = ?");
        $stmt->execute([$party_id]);
        $party = $stmt->fetch();

        if (!$party) {
            $response->getBody()->write(json_encode([
                'message' => 'Party not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($party));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response) {
        try {
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
            if (!isset($data['name']) || trim((string)$data['name']) === '') {
                $errors['name'] = 'Name is required';
            }
            if (!isset($data['alias']) || trim((string)$data['alias']) === '') {
                $errors['alias'] = 'Alias is required';
            }
            if ($errors) {
                $response->getBody()->write(json_encode([
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $db->prepare("INSERT INTO parties (name, alias) VALUES (?, ?)");
            $result = $stmt->execute([
                $data['name'], 
                $data['alias']
            ]);

            if (!$result) {
                throw new \Exception('Failed to insert city');
            }

            $newId = $db->lastInsertId();

            $created = [
                'id' => intval($newId),
                'name' => $data['name'],
                'alias' => $data['alias']
            ];

            $response->getBody()->write(json_encode($created));
            return $response
                ->withStatus(201)
                ->withHeader('Location', '/acm/counterra/api/parties/' . $newId)
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
            if (!isset($data['name']) || trim((string)$data['name']) === '') {
                $errors['name'] = 'Name is required';
            }
            if (!isset($data['alias']) || trim((string)$data['alias']) === '') {
                $errors['alias'] = 'Alias is required';
            }
            if ($errors) {
                $response->getBody()->write(json_encode([
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $db->prepare("UPDATE parties SET name = ?, alias = ? WHERE id = ?");
            $stmt->execute([
                $data['name'], 
                $data['alias'], 
                $id
            ]);

            $stmt = $db->prepare("SELECT * FROM parties WHERE id = ?");
            $stmt->execute([$id]);
            $city = $stmt->fetch();

            if (!$city) {
                $response->getBody()->write(json_encode([
                    'message' => 'City not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($city));
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

            $stmt = $db->prepare("DELETE FROM parties WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode([
                    'message' => 'City not found'
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