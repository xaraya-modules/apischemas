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
sys::import('modules.dynamicdata.class.objects.master');
/**
 * Class to manage the API schemas
**/
class xarAPISchemas_User
{
    protected static $moduleid = 18252;
    protected static $objects = [];

    public static function init(array $args = [])
    {
    }

    public static function main(array $args = [])
    {
        return $args;
    }

    public static function get_objects()
    {
        if (!empty(self::$objects)) {
            return self::$objects;
        }
        $objects = DataObjectMaster::getObjects();
        self::$objects = [];
        foreach ($objects as $objectid => $objectinfo) {
            if (intval($objectinfo['moduleid']) !== self::$moduleid) {
                continue;
            }
            self::$objects[$objectinfo['name']] = $objectinfo;
        }
        return self::$objects;
    }

    /**
     * Utility function to retrieve the list of itemtypes of this module (if any).
     * @param array    $args array of optional parameters
     * @return array the itemtypes of this module and their description
     */
    public static function itemtypes(array $args = [])
    {
        $objects = self::get_objects();
        $itemtypes = [];
        foreach ($objects as $name => $objectinfo) {
            $itemtypes[$objectinfo['itemtype']] = [
                'label' => xarVar::prepForDisplay($objectinfo['label']),
                'title' => xarVar::prepForDisplay(xarML('View #(1)', $objectinfo['label'])),
                'url'   => xarServer::getObjectURL($objectinfo['name'], 'view'),
            ];
        }
        return $itemtypes;
    }

    /**
     * utility function to pass individual item links to whoever
     * @param array    $args array of optional parameters
     *        string   $args['itemtype'] item type (optional)
     *        array    $args['itemids'] array of item ids to get
     * @return array containing the itemlink(s) for the item(s).
     */
    public static function itemlinks(array $args=[])
    {
        extract($args);

        $itemlinks = [];
        if (empty($itemtype)) {
            $itemtype = null;
        }
        if (empty($itemids)) {
            $itemids = null;
        }

        // for items managed by apischemas itself only
        $module_id = xarMod::getRegID('apischemas');
        $args = DataObjectDescriptor::getObjectID(['moduleid'  => $module_id,
                                        'itemtype'  => $itemtype]);
        if (empty($args['objectid'])) {
            return $itemlinks;
        }
        $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
        $object = DataObjectMaster::getObjectList(['objectid'  => $args['objectid'],
                                            'itemids' => $itemids,
                                            'status' => $status]);
        if (!isset($object) || (empty($object->objectid) && empty($object->table))) {
            return $itemlinks;
        }
        if (!$object->checkAccess('view')) {
            return $itemlinks;
        }

        $object->getItems();

        $properties = & $object->getProperties();
        $items = & $object->items;
        if (!isset($items) || !is_array($items) || count($items) == 0) {
            return $itemlinks;
        }

        // TODO: make configurable
        $titlefield = '';
        foreach ($properties as $name => $property) {
            // let's use the first textbox property we find for now...
            if ($property->type == 2) {
                $titlefield = $name;
                break;
            }
        }

        // if we didn't have a list of itemids, return all the items we found
        if (empty($itemids)) {
            $itemids = array_keys($items);
        }

        foreach ($itemids as $itemid) {
            if (!empty($titlefield) && isset($items[$itemid][$titlefield])) {
                $label = $items[$itemid][$titlefield];
            } else {
                $label = xarML('Item #(1)', $itemid);
            }
            // $object->getActionURL('display', $itemid)
            $itemlinks[$itemid] = ['url'   => xarServer::getObjectURL($object->name, 'display', ['itemid' => $itemid]),
                                   'title' => xarML('Display Item'),
                                   'label' => $label];
        }
        return $itemlinks;
    }
}
