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

use Xaraya\DataObject\Traits\UserApiInterface;
use Xaraya\DataObject\Traits\UserApiTrait;
use sys;

sys::import('modules.dynamicdata.class.traits.userapi');

/**
 * Class to manage the API schemas
**/
class UserApi implements UserApiInterface
{
    use UserApiTrait;

    protected static string $moduleName = 'apischemas';
    protected static int $moduleId = 18252;
    protected static int $itemtype = 0;
}
