<?php
sys::import('modules.dynamicdata.class.objects.master');
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
    protected static $moduleid = 18252;
    protected static $itemtype = 0;
    protected static $proptype_ids = array();
    protected static $dataproperty = null;
    protected static $schemas;
    protected static $fixtures;
    protected static $objects = array();

    public static function init(array $args = array())
    {
        if (isset(self::$schemas)) {
            return;
        }
        self::get_proptype_ids();
        self::$schemas = dirname(__DIR__) . '/resources/schemas';
        self::$fixtures = dirname(__DIR__) . '/resources/fixtures';
        self::get_objects();
    }

    public static function get_objects()
    {
        $objects = DataObjectMaster::getObjects();
        self::$objects = array();
        foreach ($objects as $objectid => $objectinfo) {
            if (intval($objectinfo['moduleid']) !== self::$moduleid) {
                continue;
            }
            if (intval($objectinfo['itemtype']) > self::$itemtype) {
                self::$itemtype = intval($objectinfo['itemtype']);
            }
            self::$objects[$objectinfo['name']] = $objectinfo;
        }
        return self::$objects;
    }

    public static function get_proptype_ids()
    {
        $proptypes = DataPropertyMaster::getPropertyTypes();
        self::$proptype_ids = array();
        foreach ($proptypes as $typeid => $proptype) {
            self::$proptype_ids[$proptype['name']] = $typeid;
        }
        self::$dataproperty = DataObjectMaster::getObject(array('name' => 'properties'));
        return self::$proptype_ids;
    }

    public static function create_object($info)
    {
        $objectname = self::$dd_prefix . $info->name;
        if (array_key_exists($objectname, self::$objects)) {
            //print_r(self::$objects[$objectname]);
            return self::$objects[$objectname]['objectid'];
        }
        self::$itemtype += 1;
        $objectid = DataObjectMaster::createObject(
            array(
                'name' => $objectname,
                'label' => $info->title,
                'moduleid' => self::$moduleid,
                'itemtype' => self::$itemtype
            )
        );
        $name = 'id';
        $label = 'Id';
        $type = self::$proptype_ids['itemid'];
        $seq = 1;
        $propid = self::$dataproperty->createItem(
            array(
                'itemid' => 0,
                'objectid' => $objectid,
                'name' => $name,
                'label' => $label,
                'type' => $type,
                'seq' => $seq
            )
        );
        foreach ($info->properties as $name => $property) {
            if (property_exists($property, 'format')) {
                $format = $property->format;
            } else {
                $format = $property->type;
            }
            $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
            switch ($format) {
                case 'integer':
                    $type = self::$proptype_ids['integerbox'];
                    break;
                case 'string':
                    $type = self::$proptype_ids['textbox'];
                    break;
                case 'array':
                    //$type = self::$proptype_ids['array'];
                    $type = self::$proptype_ids['textarea'];
                    // @todo handle many-to-one and many-to-many dependencies
                    //$type = self::$proptype_ids['subitems'];
                    $status = DataPropertyMaster::DD_DISPLAYSTATE_DISPLAYONLY | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
                    break;
                case 'date-time':
                case 'date':
                    $type = self::$proptype_ids['calendar'];
                    break;
                case 'uri':
                    $type = self::$proptype_ids['url'];
                    break;
                default:
                    throw new Exception('Unsupported property type ' . $property->type);
                    break;
            }
            $label = ucwords(str_replace('_', ' ', $name));
            $seq += 1;
            $propid = self::$dataproperty->createItem(
                array(
                    'itemid' => 0,
                    'objectid' => $objectid,
                    'name' => $name,
                    'label' => $label,
                    'type' => $type,
                    'status' => $status,
                    'seq' => $seq
                )
            );
        }
        return $objectid;
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
            //self::dump_schema($info);
            $schemas[$info->name] = $info;
        }
        ksort($schemas);
        foreach ($schemas as $name => $info) {
            $objectid = self::create_object($info);
        }
        self::$objects = self::get_objects();
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
        //echo json_encode($info, JSON_PRETTY_PRINT);
        echo $info->name . " " . $info->title . " " . $info->description . "\n";
        foreach ($info->properties as $name => $property) {
            if (property_exists($property, 'format')) {
                echo "\t" . $name . ": " . $property->type . " (" . $property->format . ")\n";
            } else {
                echo "\t" . $name . ": " . $property->type . "\n";
            }
        }
    }

    public static function load_items()
    {
        self::init();
        $data = array();
        foreach (scandir(self::$fixtures) as $file) {
            if (strpos($file, '.json') === false) {
                continue;
            }
            $items = self::parse_items($file);
            //self::dump_items($items);
            foreach (array_keys($items) as $model) {
                if (!array_key_exists($model, $data)) {
                    $data[$model] = array();
                }
                $data[$model] = array_merge($data[$model], $items[$model]);
            }
        }
        // @checkme handle sub-classing transport to starship and vehicle
        $parent = 'resources.transport';
        $children = array('resources.starship', 'resources.vehicle');
        foreach ($children as $model) {
            foreach (array_keys($data[$model]) as $id) {
                $data[$model][$id] = array_merge($data[$parent][$id], $data[$model][$id]);
            }
        }
        unset($data[$parent]);
        self::dump_items($data);
        foreach ($data as $model => $items) {
            //echo json_encode($items, JSON_PRETTY_PRINT);
            $objectname = self::$dd_prefix . str_replace('resources.', '', $model);
            if (!array_key_exists($objectname, self::$objects)) {
                $objectname .= 's';
                if (!array_key_exists($objectname, self::$objects)) {
                    throw new Exception('Unknown object: ' . $objectname);
                }
            }
            $dataobject = DataObjectMaster::getObject(array('name' => $objectname));
            // @todo keep array serialized/encoded for now - add relationships later
            $serialized = array();
            foreach ($dataobject->properties as $property) {
                //if ($property->type == self::$proptype_ids['array']) {
                if ($property->type == self::$proptype_ids['textarea']) {
                    $serialized[] = $property->name;
                }
            }
            foreach ($items as $id => $item) {
                // @checkme preserve itemid on import here
                $item['itemid'] = $item['id'];
                // @todo keep array serialized for now - add relationships later
                foreach ($serialized as $name) {
                    if (!array_key_exists($name, $item)) {
                        $item[$name] = array();
                    }
                    //$item[$name] = serialize($item[$name]);
                    $item[$name] = json_encode($item[$name]);
                }
                $itemid = $dataobject->createItem($item);
                if (empty($itemid) || $itemid !== $item['id']) {
                    throw new Exception('Invalid itemid ' . $itemid . 'for object ' . $objectname);
                }
            }
        }
    }

    public static function parse_items(string $file)
    {
        $content = file_get_contents(self::$fixtures . '/' . $file);
        $data = json_decode($content);
        $items = array();
        foreach ($data as $item) {
            if (!array_key_exists($item->model, $items)) {
                $items[$item->model] = array();
            }
            if (array_key_exists("pk_" . $item->pk, $items[$item->model])) {
                throw new Exception('Duplicate item ' . $item->pk . ' for model ' . $item->model);
            }
            $items[$item->model]["pk_" . $item->pk] = (array) $item->fields;
            $items[$item->model]["pk_" . $item->pk]["id"] = $item->pk;
        }
        return $items;
    }

    public static function dump_items($items)
    {
        //echo json_encode($items, JSON_PRETTY_PRINT);
        foreach (array_keys($items) as $model) {
            echo 'Model: ' . $model . ' - Items: ' . count($items[$model]) . "\n";
            //echo json_encode($items[$model], JSON_PRETTY_PRINT);
        }
    }

}
