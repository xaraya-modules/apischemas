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
 * require dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * https://github.com/nikic/FastRoute
 */
$baseDir = dirname(__DIR__, 4);
require $baseDir . '/vendor/autoload.php';

// use the FastRoute library here
//use FastRoute\Dispatcher;
//use FastRoute\RouteCollector;
//use function FastRoute\simpleDispatcher;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    DataObjectRESTHandler::sendCORSOptions();
    exit(0);
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

function send_openapi()
{
    $result = xarAPISchemas_Test::getOpenAPI();
    DataObjectRESTHandler::output($result);
}

function get_dispatcher()
{
    $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
        xarAPISchemas_Test::registerRoutes($r);
    });
    return $dispatcher;
}

function dispatch_request($method, $path)
{
    $dispatcher = get_dispatcher();
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
                $result = DataObjectRESTHandler::callHandler($handler, $vars);
                DataObjectRESTHandler::output($result);
            } catch (UnauthorizedOperationException $e) {
                DataObjectRESTHandler::output('This operation is unauthorized, please authenticate.', 401);
            } catch (ForbiddenOperationException $e) {
                DataObjectRESTHandler::output('This operation is forbidden.', 403);
            } catch (Throwable $e) {
                $result = "Exception: " . $e->getMessage();
                if ($e->getPrevious() !== null) {
                    $result .= "\nPrevious: " . $e->getPrevious()->getMessage();
                }
                $result .= "\nTrace:\n" . $e->getTraceAsString();
                DataObjectRESTHandler::output($result, 422);
            }
            break;
    }
}

function try_handler()
{
    if (empty($_SERVER['PATH_INFO'])) {
        send_openapi();
    } else {
        dispatch_request($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO']);
    }
}

try_handler();
