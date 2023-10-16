<?php
/**
 * @package modules\apischemas
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
sys::import('modules.apischemas.class.usergui');
sys::import('modules.apischemas.class.testgui');
use Xaraya\Modules\ApiSchemas\UserGui;
use Xaraya\Modules\ApiSchemas\TestGui;

/**
 * User main
 *
 * @uses UserGui::main()
 * @param array $args
 * @return string template output in HTML
 */
function apischemas_user_main(array $args = [])
{
    return UserGui::main($args);
}

/**
 * User test
 *
 * @uses TestGui::main()
 * @param array $args
 * @return string template output in HTML
 */
function apischemas_user_test(array $args = [])
{
    TestGui::init();
    return TestGui::main($args);
}
