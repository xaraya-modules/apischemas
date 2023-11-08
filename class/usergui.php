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

use sys;

sys::import('modules.dynamicdata.class.objects.factory');

/**
 * Class to manage the API schemas
**/
class UserGui
{
    /**
     * Summary of init
     * @param array<string, mixed> $args
     * @return void
     */
    public static function init(array $args = [])
    {
    }

    /**
     * Summary of main
     * @param array<string, mixed> $args
     * @return array<mixed>
     */
    public static function main(array $args = [])
    {
        return $args;
    }
}
