<?php

/**
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

use Xaraya\Modules\DynamicData\Traits\UserGuiInterface;
use Xaraya\Modules\DynamicData\Traits\UserGuiTrait;
use Xaraya\Modules\ApiSchemas\TestGui;
use sys;

sys::import('modules.dynamicdata.class.objects.factory');
sys::import('modules.dynamicdata.traits.usergui');

/**
 * Class instance to handle the ApiSchemas User GUI
**/
class UserGui implements UserGuiInterface
{
    /** @use UserGuiTrait<Module> */
    use UserGuiTrait;

    /**
     * User test
     *
     * @uses TestGui::main()
     * @param array<string, mixed> $args
     * @uses \sys::autoload()
     * @return mixed template output in HTML
     */
    public function test(array $args = [])
    {
        sys::import('modules.apischemas.testgui');
        $testgui = new TestGui('apischemas');
        $testgui->init();
        $testgui->setContext($this->getContext());
        return $testgui->main($args);
    }
}
