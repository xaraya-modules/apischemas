<?php
/**
 * @package modules\apischemas
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
sys::import('modules.apischemas.class.user');

/**
 * Utility function to retrieve the list of itemtypes of this module (if any).
 * @uses xarAPISchemas_User::itemtypes()
 * @param array    $args array of optional parameters
 * @return array the itemtypes of this module and their description
 */
function apischemas_userapi_getitemtypes(array $args = [])
{
    return xarAPISchemas_User::itemtypes($args);
}

/**
 * utility function to pass individual item links to whoever
 * @uses xarAPISchemas_User::itemlinks()
 * @param array    $args array of optional parameters
 *        string   $args['itemtype'] item type (optional)
 *        array    $args['itemids'] array of item ids to get
 * @return array containing the itemlink(s) for the item(s).
 */
function apischemas_userapi_getitemlinks(array $args=[])
{
    return xarAPISchemas_User::itemlinks($args);
}
