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

sys::import('modules.apischemas.class.import');
use Xaraya\Modules\ApiSchemas\Import;

/**
 * Initialise this module
 *
 * @access public
 * @return  boolean true on success or false on failure
**/
function apischemas_init()
{
    //Import::deleteItems();
    //Import::deleteObjects();
    Import::loadSchemas();
    Import::loadItems();

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
 * @param string $oldversion
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
    Import::deleteItems();
    Import::deleteObjects();
    //Import::loadSchemas();
    //Import::loadItems();

    return true;
}
