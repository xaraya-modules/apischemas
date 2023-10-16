<?php
/**
 * @package modules\apischemas
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules\ApiSchemas;

//require dirname(__DIR__).'/vendor/autoload.php';
//use Vural\OpenAPIFaker\OpenAPIFaker;
//use OpenAPIServer\Mock\OpenApiDataMocker;
use sys;

/**
 * Class to test the API schemas
**/
class TestGui
{
    /**
     * Initialize test with composer autoload
     * @param array $args
     * @return void
     */
    public static function init(array $args = [])
    {
        sys::autoload();
    }

    /**
     * Main test user page = index.php?module=apischemas&type=user&func=test
     * @param array $args
     * @return array
     */
    public static function main(array $args = [])
    {
        $args['path'] ??= '/articles/feed';
        $args['method'] ??= 'GET';
        $doc = TestApi::getOpenAPI();
        $operation = TestApi::findOperation($args['path'], $args['method'], $doc);
        $schema = TestApi::getResponseSchema($operation, $doc);
        $args['data'] = TestApi::buildResponse('response', $schema);
        // start by dereferencing components
        $doc['components'] = TestApi::dereference($doc['components'], $doc);
        $args['doc'] = TestApi::dereference($doc, $doc);
        return $args;
    }
}
