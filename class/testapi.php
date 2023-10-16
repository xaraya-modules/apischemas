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
use FastRoute\RouteCollector;
use BadParameterException;
use xarServer;

/**
 * Class to test the API schemas
**/
class TestApi
{
    public static string $endpoint = 'api.php';
    /** @var array<string, mixed> */
    public static array $operations = [];

    /**
     * De-reference OpenAPI document
     * @param mixed $item
     * @param mixed $doc
     * @return mixed
     */
    public static function dereference($item, $doc)
    {
        if (is_array($item)) {
            if (count($item) == 1 && array_key_exists('$ref', $item)) {
                $ref = $item['$ref'];
                if (str_starts_with($ref, '#/components/')) {
                    $parts = explode('/', $ref);
                    $item = $doc['components'][$parts[2]][$parts[3]];
                }
            }
            $info = [];
            foreach ($item as $key => $value) {
                $info[$key] = self::dereference($value, $doc);
            }
            return $info;
        }
        return $item;
    }

    /**
     * Get OpenAPI file path
     * @return string openapi file
     */
    public static function getOpenAPIFile()
    {
        return dirname(__DIR__) . '/realworld/api/openapi.json';
    }

    /**
     * Get OpenAPI document as array
     * @param mixed $vars
     * @param mixed $request
     * @return array<string, mixed>
     */
    public static function getOpenAPI($vars = [], &$request = null)
    {
        $openapi = static::getOpenAPIFile();
        $content = file_get_contents($openapi);
        $doc = json_decode($content, true);
        $doc['servers'][0]['url'] = static::getBaseURL();
        return $doc;
    }

    /**
     * Get BaseURL for API endpoint
     * @param mixed $base
     * @param mixed $path
     * @param mixed $args
     * @return string
     */
    public static function getBaseURL($base = '', $path = null, $args = [])
    {
        if (empty($path)) {
            return xarServer::getBaseURL() . self::$endpoint . $base;
        }
        return xarServer::getBaseURL() . self::$endpoint . $base . '/' . $path;
    }

    /**
     * Generic request handler for all operations - requires finding the matching operation again
     * @param array<string, mixed> $args
     * @return mixed
     */
    public static function handleRequest($args)
    {
        //return $args;
        $path = $args['server']['PATH_INFO'] ?? '';
        $method = $args['server']['REQUEST_METHOD'];
        $doc = static::getOpenAPI();
        $operation = static::findOperation($path, $method, $doc);
        $schema = static::getResponseSchema($operation, $doc);
        $path_vars = $args['path'];
        return static::buildResponse('response', $schema);
    }

    /**
     * Find operation matching path and method, possibly using regex for path params
     * @param string $path
     * @param string $method
     * @param array<string, mixed> $doc
     * @throws BadParameterException
     * @return array<string, mixed>
     */
    public static function findOperation($path, $method, $doc)
    {
        // check exact match with static paths first
        if (array_key_exists($path, $doc['paths'])) {
            if (array_key_exists($method, $doc['paths'][$path])) {
                return $doc['paths'][$path][$method];
            }
            $method = strtolower($method);
            if (array_key_exists($method, $doc['paths'][$path])) {
                return $doc['paths'][$path][$method];
            }
            throw new BadParameterException([$method, $path], 'Invalid method #(1) for path #(2)');
        }
        // sort by path length
        $paths = array_keys($doc['paths']);
        usort($paths, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        foreach ($paths as $check) {
            // skip static paths here
            if (strpos($check, '{') === false) {
                continue;
            }
            $match = preg_replace('/\{[^}]+\}/', '([^/]+)', $check);
            if (preg_match('~^' . $match . '$~', $path)) {
                if (array_key_exists($method, $doc['paths'][$check])) {
                    return $doc['paths'][$check][$method];
                }
                $method = strtolower($method);
                if (array_key_exists($method, $doc['paths'][$check])) {
                    return $doc['paths'][$check][$method];
                }
                throw new BadParameterException([$method, $path], 'Invalid method #(1) for path #(2)');
            }
        }
        throw new BadParameterException([$path], 'Invalid path #(1)');
    }

    /**
     * Magic method to call specific operation handler - no need to find matching operation here
     * @param string $method
     * @param array<string, mixed> $args
     * @throws BadParameterException
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        if (!array_key_exists($method, static::$operations)) {
            throw new BadParameterException($method, 'Invalid operation #(1)');
        }
        //return ['method' => $method, 'args' => $args];
        $operation = static::$operations[$method];
        $schema = static::getResponseSchema($operation);
        $path_vars = $args['path'];
        return static::buildResponse('response', $schema);
    }

    /**
     * Summary of getResponseSchema
     * @param array<string, mixed> $operation
     * @param ?array<string, mixed> $doc
     * @param string $statusCode
     * @param string $mediaType
     * @return array<string, mixed>
     */
    public static function getResponseSchema($operation, $doc = null, $statusCode = '200', $mediaType = 'application/json')
    {
        $response = $operation['responses'][$statusCode];
        $doc ??= static::getOpenAPI();
        $response = static::dereference($response, $doc);
        return $response['content'][$mediaType]['schema'];
    }

    /**
     * Summary of buildResponse
     * @param string $name
     * @param array<string, mixed> $schema
     * @param integer $count
     * @return mixed
     */
    public static function buildResponse($name, $schema, $count = 2)
    {
        switch ($schema['type']) {
            case 'array':
                $items = [];
                for ($i = 0; $i < $count; $i++) {
                    array_push($items, static::buildResponse($name . '[' . strval($i) . ']', $schema['items']));
                }
                return $items;
            case 'object':
                $item = [];
                foreach ($schema['properties'] as $propname => $property) {
                    $item[$propname] = static::buildResponse($propname, $property);
                }
                return $item;
            case 'string':
                if (empty($schema['format'])) {
                    return 'This is a ' . $name;
                }
                if ($schema['format'] == 'date-time') {
                    return date(DATE_RFC3339);
                }
                return $schema;
            case 'integer':
                return $count;
            case 'boolean':
                return true;
            default:
                return $schema;
        }
    }

    /**
     * Register REST API routes (in FastRoute format) - see realworld/api.php
     * @param RouteCollector $r
     * @return void
     */
    public static function registerRoutes($r)
    {
        $doc = static::getOpenAPI();
        foreach ($doc['paths'] as $path => $ops) {
            foreach ($ops as $method => $operation) {
                if (empty($operation['operationId'])) {
                    $operation['operationId'] = ucfirst($method) . strtr(ucwords(implode(' ', explode('/', strtr($path, '{}')))), ' ');
                }
                //$r->addRoute(strtoupper($method), $path, [static::class, 'handleRequest']);
                $handleOperation = 'handle' . $operation['operationId'];
                static::$operations[$handleOperation] = $operation;
                $r->addRoute(strtoupper($method), $path, [static::class, $handleOperation]);
            }
        }
    }
}
