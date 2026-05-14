<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CandidateController {

    private function fetchCandidateWithJoins($db, int $id) {
        $sql = "SELECT c.*, p.title as position_title, p.city_id as city_id,
                       cit.name as city_name,
                       par.name as party_name, par.alias as party_alias
                FROM candidates c
                JOIN positions p ON c.position_id = p.id
                JOIN cities cit ON p.city_id = cit.id
                JOIN parties par ON c.party_id = par.id
                WHERE c.id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll(Request $request, Response $response) {
        $db = (new Database())->getConnection();

        $sql = "SELECT c.*, p.title as position_title, p.city_id as city_id,
                       cit.name as city_name,
                       par.name as party_name, par.alias as party_alias
                FROM candidates c
                JOIN positions p ON c.position_id = p.id
                JOIN cities cit ON p.city_id = cit.id
                JOIN parties par ON c.party_id = par.id
                ORDER BY cit.name, p.title, c.name";

        $stmt = $db->query($sql);
        $candidates = $stmt->fetchAll();

        $response->getBody()->write(json_encode($candidates));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getOne(Request $request, Response $response, array $args) {
        $id = intval($args['id']);
        $db = (new Database())->getConnection();

        $candidate = $this->fetchCandidateWithJoins($db, $id);

        if (!$candidate) {
            $response->getBody()->write(json_encode([
                'message' => 'Candidate not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($candidate));
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

            $errors = [];
            if (!isset($data['name']) || trim((string)$data['name']) === '') {
                $errors['name'] = 'Name is required';
            }
            if (!isset($data['position_id'])) {
                $errors['position_id'] = 'Position is required';
            }
            if (!isset($data['party_id'])) {
                $errors['party_id'] = 'Party is required';
            }
            if ($errors) {
                $response->getBody()->write(json_encode([
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
            }

            $sql = "INSERT INTO candidates (name, position_id, party_id) VALUES (?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $data['name'],
                intval($data['position_id']),
                intval($data['party_id'])
            ]);

            if (!$result) {
                throw new \Exception('Failed to insert candidate');
            }

            $newId = intval($db->lastInsertId());
            $created = $this->fetchCandidateWithJoins($db, $newId);

            $response->getBody()->write(json_encode($created ?: [
                'id' => $newId,
                'name' => $data['name'],
                'position_id' => intval($data['position_id']),
                'party_id' => intval($data['party_id'])
            ]));

            return $response
                ->withStatus(201)
                ->withHeader('Location', '/acm/counterra/api/candidates/' . $newId)
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

            $errors = [];
            if (!isset($data['name']) || trim((string)$data['name']) === '') {
                $errors['name'] = 'Name is required';
            }
            if (!isset($data['position_id'])) {
                $errors['position_id'] = 'Position is required';
            }
            if (!isset($data['party_id'])) {
                $errors['party_id'] = 'Party is required';
            }
            if ($errors) {
                $response->getBody()->write(json_encode([
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
            }

            $sql = "UPDATE candidates SET name = ?, position_id = ?, party_id = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['name'],
                intval($data['position_id']),
                intval($data['party_id']),
                $id
            ]);

            $candidate = $this->fetchCandidateWithJoins($db, $id);

            if (!$candidate) {
                $response->getBody()->write(json_encode([
                    'message' => 'Candidate not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($candidate));
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

            $stmt = $db->prepare("DELETE FROM candidates WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode([
                    'message' => 'Candidate not found'
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
