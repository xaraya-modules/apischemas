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

use Xaraya\Modules\ApiSchemas\TestApiHandler;
use Xaraya\Bridge\RestAPI\RestAPIHandler;
use Xaraya\Services\xar;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    RestAPIHandler::sendCORSOptions();
    return;
}

// initialize bootstrap
sys::init();
// get Xaraya Services Class
$xar = xar::getServicesClass();
// initialize caching - delay until we need results
//$xar->cache()->init();
// initialize database - delay until caching fails
//$xar->db()->init();
// initialize modules
//$xar->mod()->init();
// initialize users
//$xar->user()->init();

// the openapi.json file for the api being tested
$openApiFile = __DIR__ . '/api/openapi.json';

// Get test API handler
$restHandler = new TestApiHandler($openApiFile, $xar);
// $restHandler->enableTimer(true);

// Handle request
$req = $xar->req();
$method = $req->getServerVar('REQUEST_METHOD') ?? 'GET';
$path = $req->getServerVar('PATH_INFO') ?? '';
$restHandler->handleRequest($method, $path);
