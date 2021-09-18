<?php

// 1. Download PHP-CRUD-API api.include.php file from https://github.com/mevdschee/php-crud-api:
// $ wget https://github.com/mevdschee/php-crud-api/raw/master/api.include.php -O php-crud-api.include.php
// 2. Configure or customize $config below
// 3. Try out Swagger UI with URL php-crud-api.php/openapi - WARNING: this provides write access by default
include 'php-crud-api.include.php';

// file: src/index.php
//namespace Tqdev\PhpCrudApi {

    use Tqdev\PhpCrudApi\Api;
    use Tqdev\PhpCrudApi\Config;
    use Tqdev\PhpCrudApi\RequestFactory;
    use Tqdev\PhpCrudApi\ResponseUtils;

    include 'var/config.system.php';
    $config = new Config([
        // 'driver' => 'mysql',
        // 'address' => 'localhost',
        // 'port' => '3306',
        'username' => $systemConfiguration['DB.UserName'],
        'password' => $systemConfiguration['DB.Password'],
        'database' => $systemConfiguration['DB.Name'],
        // 'debug' => false
        'controllers' => 'records, openapi, columns',
        'middlewares' => 'cors, authorization',
        'authorization.tableHandler' => function ($operation, $tableName) {
            return ($operation == 'list' || $operation == 'read' || $operation == 'document' || $operation == 'reflect');
        },
        'authorization.columnHandler' => function ($operation, $tableName, $columnName) {
            return !($tableName == 'xar_roles' && ($columnName == 'pass' || $columnName == 'email'));
        },
    ]);
    $request = RequestFactory::fromGlobals();
    $api = new Api($config);
    $response = $api->handle($request);
    ResponseUtils::output($response);
//}
