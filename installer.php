<?php

/**
 * Handle module installer functions
 *
 * @package modules\apischemas
 * @category Xaraya Web Applications Framework
 * @version 2.5.6
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules\ApiSchemas;

use Xaraya\Modules\InstallerClass;

/**
 * Handle module installer functions
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /**
     * Initialise this module
     *
     * @access public
     * @return  boolean true on success or false on failure
    **/
    public function init()
    {
        //Import::deleteItems();
        //Import::deleteObjects();
        Import::loadSchemas();
        Import::loadItems();

        // Installation complete; check for upgrades
        return $this->upgrade('2.0.0');
    }

    /**
     * Upgrade this module from an old version
     *
     * @param string $oldversion
     * @return boolean true on success, false on failure
     */
    public function upgrade($oldversion)
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
    public function delete()
    {
        Import::deleteItems();
        Import::deleteObjects();
        //Import::loadSchemas();
        //Import::loadItems();

        return true;
    }
}
