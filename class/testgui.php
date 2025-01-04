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

//require_once dirname(__DIR__).'/vendor/autoload.php';
//use Vural\OpenAPIFaker\OpenAPIFaker;
//use OpenAPIServer\Mock\OpenApiDataMocker;
use sys;

sys::import('modules.apischemas.class.usergui');

/**
 * Class instance to handle the ApiSchemas Test GUI
 * @uses \sys::autoload()
**/
class TestGui extends UserGui
{
    /**
     * Initialize test with composer autoload
     * @param array<string, mixed> $args
     * @return void
     */
    public function init(array $args = [])
    {
        sys::autoload();
    }

    /**
     * Main test user page = index.php?module=apischemas&type=user&func=test
     * @param array<string, mixed> $args
     * @return array<mixed>
     */
    public function main(array $args = [])
    {
        $args['path'] ??= '/articles/feed';
        $args['method'] ??= 'GET';
        $testapi = new TestApiHandler();
        $doc = $testapi->getOpenAPI();
        $operation = $testapi->findOperation($args['path'], $args['method'], $doc);
        $schema = $testapi->getResponseSchema($operation, $doc);
        $args['data'] = $testapi->buildResponse('response', $schema);
        // start by dereferencing components
        $doc['components'] = TestApiHandler::dereference($doc['components'], $doc);
        $args['doc'] = TestApiHandler::dereference($doc, $doc);
        // Pass along the context for xarTpl::module() if needed
        $args['context'] ??= $this->getContext();
        return $args;
    }
}
