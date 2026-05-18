<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->setBasePath('/acm/voterra/api');

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

// Setup / Admin
$app->get('/setup/status', [\App\Controllers\SetupController::class, 'status']);
$app->post('/setup/import', [\App\Controllers\SetupController::class, 'importJson']);
$app->post('/setup/import-csv', [\App\Controllers\SetupController::class, 'importCsv']);
$app->post('/setup/wipe', [\App\Controllers\SetupController::class, 'wipe']);
$app->post('/setup/reset-export-lock', [\App\Controllers\SetupController::class, 'resetExportLock']);

// Local data
$app->get('/positions', [\App\Controllers\PositionController::class, 'getAll']);
$app->get('/candidates', [\App\Controllers\CandidateController::class, 'getAll']);

// Voting
$app->post('/ballots/validate', [\App\Controllers\BallotController::class, 'validate']);
$app->post('/votes', [\App\Controllers\VoteController::class, 'cast']);

// Results
$app->get('/results/tally', [\App\Controllers\ResultController::class, 'getTally']);
$app->get('/results/stats', [\App\Controllers\ResultController::class, 'getStats']);
$app->get('/results/export-logs', [\App\Controllers\ResultController::class, 'getExportLogs']);
$app->get('/results/export-json', [\App\Controllers\ResultController::class, 'exportJson']);
$app->get('/results/export-csv', [\App\Controllers\ResultController::class, 'exportCsv']);
$app->get('/results/return-pdf', [\App\Controllers\ResultController::class, 'printReturnPdf']);

$app->get('/test', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['message' => 'API is alive!']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
