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
 */

namespace Xaraya\Modules\ApiSchemas;

use DataObject;
use DataObjectFactory;
use DataPropertyMaster;
use Exception;
use sys;

sys::import('modules.dynamicdata.class.objects.factory');

/**
 * Class to import the API schemas
**/
class Import
{
    protected static string $dd_prefix = 'api_';
    protected static int $moduleId = 18252;
    protected static int $itemtype = 0;
    /** @var array<string, mixed> */
    protected static array $proptype_ids = [];
    protected static ?DataObject $dataproperty = null;
    protected static string $schemas;
    protected static string $fixtures;
    protected static string $mapping;
    /** @var array<string, mixed> */
    protected static array $alias = [];
    /** @var array<string, mixed> */
    protected static array $links = [];
    /** @var array<string, mixed> */
    protected static array $labels = [];
    /** @var array<string, mixed> */
    protected static array $models = [];
    /** @var array<string, mixed> */
    protected static array $inherit = [];
    /** @var array<string, mixed> */
    protected static array $objects = [];
    protected static string $basedir;

    /**
     * Summary of init
     * @param array<string, mixed> $args
     * @return void
     */
    public static function init(array $args = [])
    {
        if (isset(self::$schemas)) {
            return;
        }
        self::$basedir = dirname(__DIR__);
        self::getPropertyTypeIds();
        self::$schemas = self::$basedir . '/resources/schemas';
        self::$fixtures = self::$basedir . '/resources/fixtures';
        self::$mapping = self::$basedir . '/resources/mapping.json';
        self::getMapping();
        self::getObjects();
    }

    /**
     * Summary of getMapping
     * @return void
     */
    public static function getMapping()
    {
        $content = file_get_contents(self::$mapping);
        $info = json_decode($content, true);
        self::$alias = $info['alias'];
        self::$links = $info['links'];
        self::$labels = $info['labels'];
        self::$models = $info['models'];
        self::$inherit = $info['inherit'];
    }

    /**
     * Summary of getObjects
     * @return mixed
     */
    public static function getObjects()
    {
        $objects = DataObjectFactory::getObjects();
        self::$objects = [];
        foreach ($objects as $objectid => $objectinfo) {
            if (intval($objectinfo['moduleid']) !== self::$moduleId) {
                continue;
            }
            if (intval($objectinfo['itemtype']) > self::$itemtype) {
                self::$itemtype = intval($objectinfo['itemtype']);
            }
            self::$objects[$objectinfo['name']] = $objectinfo;
        }
        //self::find_links();
        return self::$objects;
    }

    /**
    public static function find_links()
    {
        self::$links = array();
        foreach (self::$objects as $objectname => $info) {
            $dataobject = DataObjectFactory::getObject(array('name' => $objectname));
            foreach ($dataobject->properties as $property) {
                //if ($property->type == self::$proptype_ids['array']) {
                if ($property->type == self::$proptype_ids['textarea']) {
                    self::$links[] = array('from' => $objectname, 'to' => $property->name);
                }
            }
        }
        return self::$links;
    }
     */

    /**
     * Summary of getPropertyTypeIds
     * @return mixed
     */
    public static function getPropertyTypeIds()
    {
        $proptypes = DataPropertyMaster::getPropertyTypes();
        self::$proptype_ids = [];
        foreach ($proptypes as $typeid => $proptype) {
            self::$proptype_ids[$proptype['name']] = $typeid;
        }
        self::$dataproperty = DataObjectFactory::getObject(['name' => 'properties']);
        return self::$proptype_ids;
    }

    /**
     * Summary of createObject
     * @param mixed $info
     * @throws \Exception
     * @return mixed
     */
    public static function createObject($info)
    {
        $objectname = self::$dd_prefix . $info->name;
        if (array_key_exists($objectname, self::$objects)) {
            //print_r(self::$objects[$objectname]);
            return self::$objects[$objectname]['objectid'];
        }
        echo 'Creating object ' . $objectname . "\n";
        self::$itemtype += 1;
        $objectid = DataObjectFactory::createObject(
            [
                'name' => $objectname,
                'label' => $info->title,
                'moduleid' => self::$moduleId,
                'itemtype' => self::$itemtype,
            ]
        );
        $name = 'id';
        $label = 'Id';
        $type = self::$proptype_ids['itemid'];
        $seq = 1;
        $propid = self::$dataproperty->createItem(
            [
                'itemid' => 0,
                'objectid' => $objectid,
                'name' => $name,
                'label' => $label,
                'type' => $type,
                'defaultvalue' => '',
                'seq' => $seq,
            ]
        );
        $source = $info->name;
        foreach ($info->properties as $name => $property) {
            if (property_exists($property, 'format')) {
                $format = $property->format;
            } else {
                $format = $property->type;
            }
            $default = '';
            $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
            switch ($format) {
                case 'integer':
                    $type = self::$proptype_ids['integerbox'];
                    break;
                case 'string':
                    $type = self::$proptype_ids['textbox'];
                    // @todo add link to one-to-many relationships too, cfr. homeworld
                    if (array_key_exists($source, self::$links) && array_key_exists($name, self::$links[$source])) {
                        $type = self::$proptype_ids['deferitem'];
                        $to = self::$links[$source][$name];
                        [$target, $field] = explode('.', $to);
                        $default = 'dataobject:' . self::$dd_prefix . $target . '.' . self::$labels[$target];
                    }
                    break;
                case 'array':
                    //$type = self::$proptype_ids['array'];
                    $type = self::$proptype_ids['textarea'];
                    // @todo handle many-to-one and many-to-many dependencies
                    //$type = self::$proptype_ids['subitems'];
                    if (array_key_exists($source, self::$links) && array_key_exists($name, self::$links[$source])) {
                        $type = self::$proptype_ids['defermany'];
                        $to = self::$links[$source][$name];
                        [$target, $field] = explode('.', $to);
                        // @checkme we only create one many-to-many link object sorted by name
                        $tosort = [$source, $target];
                        sort($tosort);
                        $linkname = self::$dd_prefix . implode('_', $tosort);
                        $default = 'linkobject:' . $linkname . '.' . $source . '_id.' . $target . '_id:' . self::$dd_prefix . $target . '.' . self::$labels[$target];
                    }
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
            }
            $label = ucwords(str_replace('_', ' ', $name));
            $seq += 1;
            $propid = self::$dataproperty->createItem(
                [
                    'itemid' => 0,
                    'objectid' => $objectid,
                    'name' => $name,
                    'label' => $label,
                    'type' => $type,
                    'defaultvalue' => $default,
                    'status' => $status,
                    'seq' => $seq,
                ]
            );
        }
        return $objectid;
    }

    /**
     * Summary of checkLinks
     * @return void
     */
    public static function checkLinks()
    {
        // @checkme we only create one many-to-many link object sorted by name
        ksort(self::$links);
        $seen = [];
        foreach (self::$links as $source => $fields) {
            foreach ($fields as $from => $to) {
                [$target, $field] = explode('.', $to);
                $check = "$target:$field=$source:$from";
                if (in_array($check, $seen)) {
                    continue;
                }
                $seen[] = "$source:$from=$target:$field";
                // @checkme assuming only one many-to-many link between objects here
                $objectid = self::createLink($source, $target);
            }
        }
        self::getObjects();
    }

    /**
     * Summary of createLink
     * @param mixed $source
     * @param mixed $target
     * @return mixed
     */
    public static function createLink($source, $target)
    {
        $linkname = self::$dd_prefix . $source . '_' . $target;
        $title = ucfirst($source) . ' x ' . ucfirst($target);
        if (array_key_exists($linkname, self::$objects)) {
            //print_r(self::$objects[$linkname]);
            return self::$objects[$linkname]['objectid'];
        }
        echo 'Creating object ' . $linkname . "\n";
        self::$itemtype += 1;
        $objectid = DataObjectFactory::createObject(
            [
                'name' => $linkname,
                'label' => $title,
                'moduleid' => self::$moduleId,
                'itemtype' => self::$itemtype,
            ]
        );
        $name = 'id';
        $label = 'Id';
        $type = self::$proptype_ids['itemid'];
        $seq = 1;
        $propid = self::$dataproperty->createItem(
            [
                'itemid' => 0,
                'objectid' => $objectid,
                'name' => $name,
                'label' => $label,
                'type' => $type,
                'defaultvalue' => '',
                'seq' => $seq,
            ]
        );
        $name = $source . '_id';
        $label = ucfirst($source) . 'Id';
        $type = self::$proptype_ids['deferitem'];
        $seq += 1;
        $propid = self::$dataproperty->createItem(
            [
                'itemid' => 0,
                'objectid' => $objectid,
                'name' => $name,
                'label' => $label,
                'type' => $type,
                'defaultvalue' => 'dataobject:' . self::$dd_prefix . $source . '.' . self::$labels[$source],
                'seq' => $seq,
            ]
        );
        $name = $target . '_id';
        $label = ucfirst($target) . 'Id';
        $type = self::$proptype_ids['deferitem'];
        $seq += 1;
        $propid = self::$dataproperty->createItem(
            [
                'itemid' => 0,
                'objectid' => $objectid,
                'name' => $name,
                'label' => $label,
                'type' => $type,
                'defaultvalue' => 'dataobject:' . self::$dd_prefix . $target . '.' . self::$labels[$target],
                'seq' => $seq,
            ]
        );
        return $objectid;
    }

    /**
     * Summary of loadSchemas
     * @return void
     */
    public static function loadSchemas()
    {
        self::init();
        $schemas = [];
        foreach (scandir(self::$schemas) as $file) {
            if (strpos($file, '.json') === false) {
                continue;
            }
            echo 'Loading schema ' . $file . "\n";
            $info = self::parseSchema($file);
            //self::dumpSchema($info);
            $schemas[$info->name] = $info;
        }
        ksort($schemas);
        foreach ($schemas as $name => $info) {
            $objectid = self::createObject($info);
        }
        self::getObjects();
        self::checkLinks();
    }

    /**
     * Summary of parseSchema
     * @param string $file
     * @return mixed
     */
    public static function parseSchema(string $file)
    {
        $content = file_get_contents(self::$schemas . '/' . $file);
        $schema = json_decode($content);
        $schema->file = $file;
        $schema->name = str_replace('.json', '', $file);
        return $schema;
    }

    /**
     * Summary of dumpSchema
     * @param mixed $info
     * @return void
     */
    public static function dumpSchema($info)
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

    /**
     * Summary of deleteObjects
     * @throws \Exception
     * @return void
     */
    public static function deleteObjects()
    {
        self::init();
        foreach (self::$objects as $objectname => $info) {
            echo 'Deleting object ' . $objectname . "\n";
            $result = DataObjectFactory::deleteObject(['name' => $objectname]);
            if (empty($result)) {
                throw new Exception('Error deleting object ' . $objectname);
            }
        }
    }

    /**
     * Summary of loadItems
     * @throws \Exception
     * @return void
     */
    public static function loadItems()
    {
        self::init();
        $data = [];
        foreach (scandir(self::$fixtures) as $file) {
            if (strpos($file, '.json') === false) {
                continue;
            }
            $items = self::parseItems($file);
            //self::dumpItems($items);
            foreach (array_keys($items) as $model) {
                if (!array_key_exists($model, $data)) {
                    $data[$model] = [];
                }
                $data[$model] = array_merge($data[$model], $items[$model]);
            }
        }
        // @checkme handle sub-classing transport to starship and vehicle
        foreach (array_keys(self::$inherit) as $model) {
            $parent = self::$inherit[$model];
            foreach (array_keys($data[$model]) as $id) {
                $data[$model][$id] = array_merge($data[$parent][$id], $data[$model][$id]);
            }
        }
        foreach (array_unique(array_values(self::$inherit)) as $parent) {
            unset($data[$parent]);
        }
        self::dumpItems($data);
        foreach ($data as $model => $items) {
            //echo json_encode($items, JSON_PRETTY_PRINT);
            $schema = self::$models[$model];
            $objectname = self::$dd_prefix . $schema;
            if (!array_key_exists($objectname, self::$objects)) {
                throw new Exception('Unknown object: ' . $objectname);
            }
            echo 'Loading items for object ' . $objectname . ': ' . count($items) . "\n";
            $dataobject = DataObjectFactory::getObject(['name' => $objectname]);
            $datalinks = [];
            $datatarget = [];
            foreach (self::$links[$schema] as $from => $to) {
                [$target, $field] = explode('.', $to);
                // @checkme we only create one many-to-many link object sorted by name
                $tosort = [$schema, $target];
                sort($tosort);
                $linkname = self::$dd_prefix . implode('_', $tosort);
                echo 'Adding links for ' . $from . ' in ' . $linkname . "\n";
                $datalinks[$from] = DataObjectFactory::getObject(['name' => $linkname]);
                $datatarget[$from] = $target;
            }
            // @todo keep array serialized/encoded for now - add relationships later
            $serialized = [];
            foreach ($dataobject->properties as $property) {
                //if ($property->type == self::$proptype_ids['array']) {
                if ($property->type == self::$proptype_ids['textarea']) {
                    $serialized[] = $property->name;
                } elseif ($property->type == self::$proptype_ids['defermany']) {
                    $serialized[] = $property->name;
                } elseif ($property->type == self::$proptype_ids['deferitem']) {
                    $serialized[] = $property->name;
                }
            }
            foreach ($items as $id => $item) {
                // @checkme preserve itemid on import here
                $item['itemid'] = $item['id'];
                // @todo keep array serialized for now - add relationships later
                foreach ($serialized as $name) {
                    if (!array_key_exists($name, $item)) {
                        $item[$name] = null;
                    } elseif (is_array($item[$name])) {
                        foreach ($item[$name] as $val) {
                            $link = ['id' => 0, $schema . '_id' => $item['id'], $datatarget[$name] . '_id' => $val];
                            $linkid = $datalinks[$name]->createItem($link);
                        }
                        //$item[$name] = serialize($item[$name]);
                        $item[$name] = json_encode($item[$name]);
                        // @todo add link to one-to-many relationships too, cfr. homeworld
                    } elseif (!empty($item[$name]) && is_numeric($item[$name])) {
                        $link = ['id' => 0, $schema . '_id' => $item['id'], $datatarget[$name] . '_id' => intval($item[$name])];
                        $linkid = $datalinks[$name]->createItem($link);
                    } else {
                        //throw new Exception('Invalid field ' . $name . '=' . $item[$name] . ' for object ' . $objectname);
                    }
                }
                $itemid = $dataobject->createItem($item);
                if (empty($itemid) || $itemid !== $item['id']) {
                    throw new Exception('Invalid itemid ' . $itemid . ' for object ' . $objectname);
                }
            }
        }
    }

    /**
     * Summary of parseItems
     * @param string $file
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function parseItems(string $file)
    {
        $content = file_get_contents(self::$fixtures . '/' . $file);
        $data = json_decode($content);
        $items = [];
        foreach ($data as $item) {
            if (!array_key_exists($item->model, $items)) {
                $items[$item->model] = [];
            }
            if (array_key_exists("pk_" . $item->pk, $items[$item->model])) {
                throw new Exception('Duplicate item ' . $item->pk . ' for model ' . $item->model);
            }
            $items[$item->model]["pk_" . $item->pk] = (array) $item->fields;
            $items[$item->model]["pk_" . $item->pk]["id"] = $item->pk;
        }
        return $items;
    }

    /**
     * Summary of dumpItems
     * @param mixed $items
     * @return void
     */
    public static function dumpItems($items)
    {
        //echo json_encode($items, JSON_PRETTY_PRINT);
        foreach (array_keys($items) as $model) {
            echo 'Model: ' . $model . ' - Items: ' . count($items[$model]) . "\n";
            //echo json_encode($items[$model], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Summary of deleteItems
     * @throws \Exception
     * @return void
     */
    public static function deleteItems()
    {
        self::init();
        foreach (self::$objects as $objectname => $info) {
            $objectlist = DataObjectFactory::getObjectList(['name' => $objectname]);
            $items = $objectlist->getItems();
            echo 'Deleting items for object ' . $objectname . ': ' . count($items) . "\n";
            $dataobject = DataObjectFactory::getObject(['name' => $objectname]);
            foreach ($items as $itemid => $item) {
                //echo $itemid . ': ' . json_encode($item) . "\n";
                $itemid = $dataobject->deleteItem(['itemid' => $itemid]);
                if (empty($itemid)) {
                    throw new Exception('Error deleting itemid ' . $item['id'] . ' for object ' . $objectname);
                }
            }
        }
    }
}
