<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database;

class AuthController {
    public function login(Request $request, Response $response) {
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            error_log("Auth login: parsed body missing or invalid");
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Invalid request body']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        error_log("Auth login attempt for username: " . $username);

        $db = (new Database())->getConnection();
        if (!$db) {
            error_log("Auth login: database connection failed");
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            error_log("Auth login: user not found for username: " . $username);
        }

        if ($user && password_verify($password, $user['password'])) {
            $result = [
                'status' => 'success',
                'token' => 'acm_secure_session_' . bin2hex(random_bytes(16)),
                'user' => ['username' => $user['username']]
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        }

        if ($user) {
            error_log("Auth login: password_verify failed for username: " . $username);
        }

        $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Invalid credentials']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}