<?php
/**
 * Class to manage the API schemas
 *
 * @package modules\apischemas
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
**/
class xarAPISchemas_Manager
{
    protected static $dd_prefix = 'api_';
    protected static $schemas;
    protected static $fixtures;

    public static function init(array $args = array())
    {
        if (isset(self::$schemas)) {
            return;
        }
        self::$schemas = dirname(__DIR__) . '/resources/schemas';
        self::$fixtures = dirname(__DIR__) . '/resources/fixtures';
    }

    public static function load_schemas()
    {
        self::init();
        $schemas = array();
        foreach (scandir(self::$schemas) as $file) {
            if (strpos($file, '.json') === false) {
                continue;
            }
            $info = self::parse_schema($file);
            self::dump_schema($info);
            $schemas[$info->name] = $info;
        }
    }

    public static function parse_schema(string $file)
    {
        $content = file_get_contents(self::$schemas . '/' . $file);
        $schema = json_decode($content);
        $schema->file = $file;
        $schema->name = str_replace('.json', '', $file);
        return $schema;
    }

    public static function dump_schema($info)
    {
        echo json_encode($info, JSON_PRETTY_PRINT);
        echo $info->name . " " . $info->title . " " . $info->description . "\n";
        foreach ($info->properties as $name => $property) {
            if (property_exists($property, 'format')) {
                echo "\t" . $name . ": " . $property->type . " (" . $property->format . ")\n";
            } else {
                echo "\t" . $name . ": " . $property->type . "\n";
            }
        }
    }
}

xarAPISchemas_Manager::load_schemas();
