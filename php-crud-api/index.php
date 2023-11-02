<?php
/**
 * File: src/index.php
 *
 * 1. Install mevdschee/php-crud-api package with composer
 * 2. Configure or customize $config below
 * 3. Try out Swagger UI with URL index.php/openapi - WARNING: this provides write access by default
 */

namespace Tqdev\PhpCrudApi;

use Tqdev\PhpCrudApi\Api;
use Tqdev\PhpCrudApi\Config\Config;
use Tqdev\PhpCrudApi\RequestFactory;
use Tqdev\PhpCrudApi\ResponseUtils;
use sys;
use xarSystemVars;

$baseDir = dirname(__DIR__, 4);
require_once $baseDir . '/vendor/autoload.php';
sys::init();

$config = new Config([
    // 'driver' => 'mysql',
    // 'address' => 'localhost',
    // 'port' => '3306',
    'username' => xarSystemVars::get(sys::CONFIG, 'DB.UserName'),
    'password' => xarSystemVars::get(sys::CONFIG, 'DB.Password'),
    'database' => xarSystemVars::get(sys::CONFIG, 'DB.Name'),
    // 'debug' => false
    'controllers' => 'records,openapi,status',
    //'controllers' => 'records,openapi,columns,status',
    'middlewares' => 'cors,authorization,pageLimits',
    'authorization.tableHandler' => function ($operation, $tableName) {
        return ($operation == 'list' || $operation == 'read' || $operation == 'document' || $operation == 'reflect');
    },
    'authorization.columnHandler' => function ($operation, $tableName, $columnName) {
        return !($tableName == 'xar_roles' && ($columnName == 'pass' || $columnName == 'email'));
    },
    'pageLimits.pages' => 10,
    'pageLimits.records' => 100,
    'tables' => 'xar_dynamic_objects,xar_dynamic_properties,xar_dynamic_data',
    'mapping' => 'xar_dynamic_objects=objects,xar_dynamic_properties=properties,xar_dynamic_data=dynamic_data',
    'basePath' => '/code/modules/apischemas/php-crud-api/index.php',
]);
$request = RequestFactory::fromGlobals();
$api = new Api($config);
$response = $api->handle($request);
ResponseUtils::output($response);
