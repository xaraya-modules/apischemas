<?php

/**
 * Entrypoint for handling REST API calls based on OpenAPI spec
 *
 * Note: this assumes you install symfony/routing with composer
 * and use composer autoload in the entrypoint, see e.g. rst.php
 *
 * $ composer require --dev symfony/routing symfony/config
 * $ head html/rst.php
 * <?php
 * ...
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * @see https://github.com/nikic/FastRoute
 * @see https://github.com/symfony/routing
 */
$baseDir = dirname(__DIR__, 4);
require_once $baseDir . '/vendor/autoload.php';

// use the nikic FastRoute library here
//use Xaraya\Routing\FastRouter;
// use the Symfony Routing component here
use Xaraya\Routing\Routing;
use Xaraya\Routing\RouterInterface;
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
 * @param TestApiHandler $restHandler
 * @return void
 */
function send_openapi($restHandler)
{
    // move away from static methods for context
    $result = $restHandler->getOpenAPI();
    $restHandler->output($result);
}

/**
 * Summary of get_router
 * @param TestApiHandler $restHandler
 * @return RouterInterface
 */
function get_router($restHandler)
{
    //$cacheFile = sys::varpath() . '/cache/api/test_api_routes.php';
    //$router = new FastRouter($restHandler->getRoutes(...), $cacheFile);
    $cacheFile = null;
    $router = new Routing($restHandler->getRoutes(...), $cacheFile);
    return $router;
}

/**
 * Summary of handle_request
 * @param string $method
 * @param string $path
 * @param RouterInterface $router
 * @param TestApiHandler $restHandler
 * @return void
 */
function handle_request($method, $path, $router, $restHandler)
{
    // $restHandler::setTimer('register');
    [$handler, $vars] = $router->match($path, $method);
    if (empty($handler)) {
        switch ((string) $vars['status']) {
            case '404':
                // ... 404 Not Found
                http_response_code(404);
                break;
            case '405':
                // ... 405 Method Not Allowed
                if (!empty($vars['methods'])) {
                    header('Allow: ' . implode(', ', $vars['methods']));
                }
                http_response_code(405);
                break;
        }
        return;
    }
    // $restHandler::setTimer('dispatch');
    // ... call $handler with $vars
    try {
        [$result, $context] = $restHandler->callHandler($handler, $vars);
        $restHandler->output($result);
    } catch (UnauthorizedOperationException $e) {
        $restHandler->output('This operation is unauthorized, please authenticate.', 401);
    } catch (ForbiddenOperationException $e) {
        $restHandler->output('This operation is forbidden.', 403);
    } catch (Throwable $e) {
        $result = "Exception: " . $e->getMessage();
        if ($e->getPrevious() !== null) {
            $result .= "\nPrevious: " . $e->getPrevious()->getMessage();
        }
        $result .= "\nTrace:\n" . $e->getTraceAsString();
        $restHandler->output($result, 422);
    }
}

/**
 * Summary of try_handler
 * @param TestApiHandler $restHandler
 * @return void
 */
function try_handler($restHandler)
{
    if (empty($_SERVER['PATH_INFO'])) {
        send_openapi($restHandler);
    } else {
        $router = get_router($restHandler);
        handle_request($_SERVER['REQUEST_METHOD'], $_SERVER['PATH_INFO'], $router, $restHandler);
    }
}

// the openapi.json file for the api being tested
$openApiFile = __DIR__ . '/api/openapi.json';

$restHandler = new TestApiHandler($openApiFile);
try_handler($restHandler);
