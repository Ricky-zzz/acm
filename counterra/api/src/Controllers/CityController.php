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

        $response->getBody()->write(json_encode($cities));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getOne(Request $request, Response $response, array $args) {
        $city_id = intval($args['id']);
        $db = (new Database())->getConnection();

        $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
        $stmt->execute([$city_id]);
        $city = $stmt->fetch();

        if (!$city) {
            $response->getBody()->write(json_encode([
                'message' => 'City not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $queryParams = $request->getQueryParams();
        $includeRaw = (string)($queryParams['include'] ?? '');
        $includes = array_filter(array_map('trim', explode(',', $includeRaw)));

        $includePositions = in_array('positions', $includes, true);
        $includeCandidates = in_array('candidates', $includes, true);

        if ($includePositions) {
            $stmt = $db->prepare("SELECT * FROM positions WHERE city_id = ?");
            $stmt->execute([$city_id]);
            $positions = $stmt->fetchAll();

            if ($includeCandidates) {
                foreach ($positions as &$pos) {
                    $stmt = $db->prepare("SELECT * FROM candidates WHERE position_id = ?");
                    $stmt->execute([$pos['id']]);
                    $pos['candidates'] = $stmt->fetchAll();
                }
                unset($pos);
            }

            $city['positions'] = $positions;
        }

        $response->getBody()->write(json_encode($city));
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
            if (!isset($data['councilor_limit'])) {
                $errors['councilor_limit'] = 'Councilor limit is required';
            }
            if ($errors) {
                $response->getBody()->write(json_encode([
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
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

            $created = [
                'id' => intval($newId),
                'name' => $data['name'],
                'councilor_limit' => intval($data['councilor_limit'])
            ];

            $response->getBody()->write(json_encode($created));
            return $response
                ->withStatus(201)
                ->withHeader('Location', '/acm/counterra/api/cities/' . $newId)
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
            if (!isset($data['councilor_limit'])) {
                $errors['councilor_limit'] = 'Councilor limit is required';
            }
            if ($errors) {
                $response->getBody()->write(json_encode([
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $db->prepare("UPDATE cities SET name = ?, councilor_limit = ? WHERE id = ?");
            $stmt->execute([
                $data['name'], 
                intval($data['councilor_limit']), 
                $id
            ]);

            $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
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

            $stmt = $db->prepare("DELETE FROM cities WHERE id = ?");
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