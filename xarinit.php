<?php
/**
 * Initialise the apischemas module
 *
 * @package modules\apischemas
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

/**
 * Initialise this module
 *
 * @access public
 * @return  boolean true on success or false on failure
**/
function apischemas_init()
{
    sys::import('modules.apischemas.class.api_import');
    //xarAPISchemas_Import::delete_items();
    //xarAPISchemas_Import::delete_objects();
    xarAPISchemas_Import::load_schemas();
    xarAPISchemas_Import::load_items();

    // Installation complete; check for upgrades
    return apischemas_upgrade('2.0.0');
}

/**
 * Activate this module
 *
 * @access public
 * @return boolean
 */
function apischemas_activate()
{
    return true;
}

/**
 * Deactivate this module
 *
 * @access public
 * @return boolean
 */
function apischemas_deactivate()
{
    return true;
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @return boolean true on success, false on failure
 */

function apischemas_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0':
            break;
        default:
            break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @return boolean
 */
function apischemas_delete()
{
    sys::import('modules.apischemas.class.api_import');
    xarAPISchemas_Import::delete_items();
    xarAPISchemas_Import::delete_objects();
    //xarAPISchemas_Import::load_schemas();
    //xarAPISchemas_Import::load_items();

    return true;
}
