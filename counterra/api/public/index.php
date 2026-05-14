<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->setBasePath('/acm/counterra/api');


$app->addBodyParsingMiddleware();

$app->addRoutingMiddleware();

$app->addErrorMiddleware(true, true, true);

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*') 
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// Auth
$app->post('/login', [\App\Controllers\AuthController::class, 'login']);

// Cities
$app->get('/cities', [\App\Controllers\CityController::class, 'getAll']);
$app->post('/cities', [\App\Controllers\CityController::class, 'create']);
$app->put('/cities/{id}', [\App\Controllers\CityController::class, 'update']);
$app->delete('/cities/{id}', [\App\Controllers\CityController::class, 'delete']);
$app->get('/cities/{id}', [\App\Controllers\CityController::class, 'getOne']);
    
// Parties
$app->get('/parties', [\App\Controllers\PartyController::class, 'getAll']);
$app->post('/parties', [\App\Controllers\PartyController::class, 'create']);
$app->put('/parties/{id}', [\App\Controllers\PartyController::class, 'update']);
$app->delete('/parties/{id}', [\App\Controllers\PartyController::class, 'delete']);
$app->get('/parties/{id}', [\App\Controllers\PartyController::class, 'getOne']);

// Positions
$app->get('/positions', [\App\Controllers\PositionController::class, 'getAll']);
$app->post('/positions', [\App\Controllers\PositionController::class, 'create']);
$app->put('/positions/{id}', [\App\Controllers\PositionController::class, 'update']);
$app->delete('/positions/{id}', [\App\Controllers\PositionController::class, 'delete']);
$app->get('/positions/{id}', [\App\Controllers\PositionController::class, 'getOne']);

// Candidates
$app->get('/candidates', [\App\Controllers\CandidateController::class, 'getAll']);
$app->post('/candidates', [\App\Controllers\CandidateController::class, 'create']);
$app->put('/candidates/{id}', [\App\Controllers\CandidateController::class, 'update']);
$app->delete('/candidates/{id}', [\App\Controllers\CandidateController::class, 'delete']);
$app->get('/candidates/{id}', [\App\Controllers\CandidateController::class, 'getOne']);

// Ballots
$app->get('/ballots', [\App\Controllers\BallotController::class, 'getAll']);
$app->post('/ballots/generate', [\App\Controllers\BallotController::class, 'generate']);
$app->get('/cities/{id}/print', [\App\Controllers\BallotController::class, 'printBallots']);

$app->get('/test', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['message' => 'API is alive!']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();