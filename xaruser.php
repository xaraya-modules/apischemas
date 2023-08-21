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
sys::import('modules.apischemas.class.test');

/**
 * User main
 *
 * @uses xarAPISchemas_User::main()
 * @param array $args
 * @return string template output in HTML
 */
function apischemas_user_main(array $args = [])
{
    return xarAPISchemas_User::main($args);
}

/**
 * User test
 *
 * @uses xarAPISchemas_Test::main()
 * @param array $args
 * @return string template output in HTML
 */
function apischemas_user_test(array $args = [])
{
    xarAPISchemas_Test::init();
    return xarAPISchemas_Test::main($args);
}
