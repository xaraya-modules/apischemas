<?php
/**
 * @package modules\apischemas
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
sys::import('modules.apischemas.class.testgui');
use Xaraya\Modules\ApiSchemas\TestGui;

/**
 * User main
 *
 * @uses UserGui::main()
 * @param array<string, mixed> $args
 * @param mixed $context
 * @return mixed template output in HTML
 */
function apischemas_user_main(array $args = [], $context = null)
{
    $usergui = xarMod::getGUI('apischemas');
    $usergui->setContext($context);
    return $usergui->main($args);
}

/**
 * User test
 *
 * @uses TestGui::main()
 * @param array<string, mixed> $args
 * @param mixed $context
 * @uses \sys::autoload()
 * @return mixed template output in HTML
 */
function apischemas_user_test(array $args = [], $context = null)
{
    $testgui = new TestGui('apischemas');
    $testgui->init();
    $testgui->setContext($context);
    return $testgui->main($args);
}
