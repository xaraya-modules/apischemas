<?php

require_once dirname(__DIR__, 2) . '/xaraya-core/phpstan-bootstrap.php';
if (!class_exists('\Xaraya\Modules\ApiSchemas\UserApi')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
