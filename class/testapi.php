<?php

/**
 * @package modules\apischemas
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
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
use Xaraya\Context\ContextFactory;
use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use Xaraya\Bridge\RestAPI\RestAPIHandler;
use Xaraya\Bridge\RestAPI\RestAPIRoutes;
use BadParameterException;
use xarServer;

/**
 * Generic REST API handler based on openapi.json file to test the API schemas
 * @phpstan-import-type RouteDef from RestAPIRoutes
**/
class TestApiHandler implements ContextInterface
{
    use ContextTrait;

    public static string $endpoint = 'api.php';
    /** @var array<string, mixed> */
    public array $operations = [];
    public string $openApiFile;
    protected ?RestAPIHandler $restApiHandler = null;

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
     * @param string $openApiFile
     */
    public function __construct($openApiFile = null)
    {
        $this->openApiFile = $openApiFile ?? dirname(__DIR__) . '/realworld/api/openapi.json';
    }

    /**
     * Get OpenAPI file path
     * @return string openapi file
     */
    public function getOpenAPIFile()
    {
        return $this->openApiFile;
    }

    /**
     * Get OpenAPI document as array
     * @param mixed $vars
     * @param mixed $context
     * @return array<string, mixed>
     */
    public function getOpenAPI($vars = [], $context = null)
    {
        $openapi = $this->getOpenAPIFile();
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
    public function handleRequest($args)
    {
        $path = $args['server']['PATH_INFO'] ?? '';
        $method = $args['server']['REQUEST_METHOD'];
        $doc = $this->getOpenAPI();
        $operation = $this->findOperation($path, $method, $doc);
        $schema = $this->getResponseSchema($operation, $doc);
        return $this->buildResponse('response', $schema);
    }

    /**
     * Find operation matching path and method, possibly using regex for path params
     * @param string $path
     * @param string $method
     * @param array<string, mixed> $doc
     * @throws BadParameterException
     * @return array<string, mixed>
     */
    public function findOperation($path, $method, $doc)
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
     * Get operation by name
     * @param string $name
     * @throws \BadParameterException
     * @return array<string, mixed>
     */
    public function getOperation($name)
    {
        if (empty($this->operations)) {
            $this->getRoutes();
        }
        // check for operation handler
        if (!array_key_exists($name, $this->operations)) {
            throw new BadParameterException($name, 'Invalid operation #(1)');
        }
        return $this->operations[$name];
    }

    /**
     * Add operation by name
     * @param string $name
     * @param array<string, mixed> $operation
     * @return void
     */
    public function addOperation($name, $operation)
    {
        $this->operations[$name] = $operation;
    }

    /**
     * Magic method to call specific operation handler - no need to find matching operation here
     * @param string $method
     * @param array<string, mixed> $args
     * @throws BadParameterException
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        // get operation by name
        $operation = $this->getOperation($method);
        $schema = $this->getResponseSchema($operation);
        return $this->buildResponse('response', $schema);
    }

    /**
     * Summary of getResponseSchema
     * @param array<string, mixed> $operation
     * @param ?array<string, mixed> $doc
     * @param string $statusCode
     * @param string $mediaType
     * @return array<string, mixed>
     */
    public function getResponseSchema($operation, $doc = null, $statusCode = '200', $mediaType = 'application/json')
    {
        $response = $operation['responses'][$statusCode];
        $doc ??= $this->getOpenAPI();
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
    public function buildResponse($name, $schema, $count = 2)
    {
        switch ($schema['type']) {
            case 'array':
                $items = [];
                for ($i = 0; $i < $count; $i++) {
                    array_push($items, $this->buildResponse($name . '[' . strval($i) . ']', $schema['items']));
                }
                return $items;
            case 'object':
                $item = [];
                foreach ($schema['properties'] as $propname => $property) {
                    $item[$propname] = $this->buildResponse($propname, $property);
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
     * Summary of getRestHandler
     * @return RestAPIHandler
     */
    public function getRestApiHandler()
    {
        $this->restApiHandler ??= new RestAPIHandler();
        return $this->restApiHandler;
    }

    /**
     * Summary of callHandler - different processing for REST API - see rst.php
     * @param mixed $handler
     * @param array<string, mixed> $vars
     * @param mixed $request
     * @return mixed
     */
    public function callHandler($handler, $vars, &$request = null)
    {
        // return $this->getRestApiHandler()->callHandler($handler, $vars, $request);
        $context = ContextFactory::fromRequest($request, __METHOD__);
        $context['mediatype'] = '';
        $this->setContext($context);
        // get handler instance with context
        $handler = $this->resolveHandler($handler, $context);
        $result = $handler($vars);
        return [$result, $context];
    }

    /**
     * Summary of resolveHandler
     * @param mixed $handler
     * @param mixed $context
     * @throws \BadParameterException
     * @return mixed
     */
    public function resolveHandler($handler, &$context)
    {
        if (is_array($handler) && is_string($handler[0])) {
            if (is_a($handler[0], $this::class, true)) {
                $handler[0] = clone $this;
                $handler[0]->setContext($context);
            } else {
                throw new BadParameterException($handler[0], 'Invalid handler #(1)');
            }
        }
        return $handler;
    }

    /**
     * Send Content-Type and JSON result to the browser
     * @param mixed $result
     * @param mixed $status
     * @param mixed $context @deprecated 2.6.3 switch to instance methods
     * @return void
     */
    public function emitResponse($result, $status = 200, $context = null)
    {
        $this->output($result, $status);
    }

    /**
     * Summary of output
     * @param mixed $result
     * @param mixed $status
     * @return void
     */
    public function output($result, $status = 200)
    {
        // $this->getRestApiHandler()->output($result, $status);
        if (!headers_sent() && $status !== 200) {
            http_response_code($status);
        }
        header('Content-Type: application/json; charset=utf-8');
        try {
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            echo '{"JSON Exception": ' . json_encode($e->getMessage()) . '}';
        }
    }

    /**
     * Get REST API routes (in generic format) - see realworld/api.php
     * @return array<string, RouteDef> array of name => [method(s), path, handler, options = []]
     */
    public function getRoutes()
    {
        // default handler is $this here - see resolveHandler()
        $handler = $this::class;
        $extra = [];
        $routes = [];

        $doc = $this->getOpenAPI();
        foreach ($doc['paths'] as $path => $ops) {
            foreach ($ops as $method => $operation) {
                if (empty($operation['operationId'])) {
                    $operation['operationId'] = ucfirst($method) . strtr(ucwords(implode(' ', explode('/', strtr($path, '{}', '')))), ' ', '');
                }
                // register operation by name + use as handler method in routes
                $handleOperation = 'handle' . $operation['operationId'];
                $this->addOperation($handleOperation, $operation);
                $routes[$handleOperation] = [strtoupper($method), $path, [$handler, $handleOperation], $extra];
            }
        }

        return $routes;
    }
}
