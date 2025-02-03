<?php
/**
 * Entrypoint for handling REST API calls based on OpenAPI spec
 *
 * Note: this assumes you install fast-route with composer
 * and use composer autoload in the entrypoint, see e.g. rst.php
 *
 * $ composer require --dev nikic/fast-route
 * $ head html/rst.php
 * <?php
 * ...
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * https://github.com/nikic/FastRoute
 */
$baseDir = dirname(__DIR__, 4);
require_once $baseDir . '/vendor/autoload.php';

// use the FastRoute library here
//use FastRoute\Dispatcher;
//use FastRoute\RouteCollector;
//use function FastRoute\simpleDispatcher;
use Xaraya\Modules\ApiSchemas\TestApiHandler;
use Xaraya\Bridge\RestAPI\RestAPIHandler;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    RestAPIHandler::sendCORSOptions();
    return;
}

// initialize bootstrap
sys::init();
// initialize caching - delay until we need results
//xarCache::init();
// initialize database - delay until caching fails
//xarDatabase::init();
// initialize modules
//xarMod::init();
// initialize users
//xarUser::init();

/**
 * Summary of send_openapi
 * @param mixed $restHandler
 * @return void
 */
function send_openapi($restHandler)
{
    $result = $restHandler->getOpenAPI();
    $restHandler->emitResponse($result);
}

/**
 * Summary of get_dispatcher
 * @param mixed $restHandler
 * @return FastRoute\Dispatcher
 */
function get_dispatcher($restHandler)
{
    $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) use ($restHandler) {
        $restHandler->registerRoutes($r);
    });
    return $dispatcher;
}

/**
 * Summary of dispatch_request
 * @param string $method
 * @param string $path
 * @param FastRoute\Dispatcher $dispatcher
 * @param mixed $restHandler
 * @return void
 */
function dispatch_request($method, $path, $dispatcher, $restHandler)
{
    $routeInfo = $dispatcher->dispatch($method, $path);
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            // ... 404 Not Found
            http_response_code(404);
            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            // ... 405 Method Not Allowed
            header('Allow: ' . implode(', ', $allowedMethods));
            http_response_code(405);
            break;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            // ... call $handler with $vars
            try {
                [$result, $context] = $restHandler->callHandler($handler, $vars);
                $restHandler->emitResponse($result, 200, $context);
            } catch (UnauthorizedOperationException $e) {
                $restHandler->emitResponse('This operation is unauthorized, please authenticate.', 401);
            } catch (ForbiddenOperationException $e) {
                $restHandler->emitResponse('This operation is forbidden.', 403);
            } catch (Throwable $e) {
                $result = "Exception: " . $e->getMessage();
                if ($e->getPrevious() !== null) {
                    $result .= "\nPrevious: " . $e->getPrevious()->getMessage();
                }
                $result .= "\nTrace:\n" . $e->getTraceAsString();
                $restHandler->emitResponse($result, 422);
            }
            break;
    }
}

/**
 * Summary of try_handler
 * @param mixed $restHandler
 * @return void
 */
function try_handler($restHandler)
{
    if (empty($_SERVER['PATH_INFO'])) {
        send_openapi($restHandler);
    } else {
        $dispatcher = get_dispatcher($restHandler);
        dispatch_request($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'], $dispatcher, $restHandler);
    }
}

// the openapi.json file for the api being tested
$openApiFile = __DIR__ . '/api/openapi.json';

$restHandler = new TestApiHandler($openApiFile);
try_handler($restHandler);
