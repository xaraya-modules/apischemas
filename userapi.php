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

use Xaraya\Modules\DynamicData\Traits\UserApiInterface;
use Xaraya\Modules\DynamicData\Traits\UserApiTrait;

/**
 * Class to handle the ApiSchemas User API
**/
class UserApi implements UserApiInterface
{
    /** @use UserApiTrait<Module> */
    use UserApiTrait;
}
