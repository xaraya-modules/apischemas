<?php

namespace Xaraya\Modules\ApiSchemas\Tests;

use Xaraya\Modules\TestHelper;
use Xaraya\Modules\ApiSchemas\UserGui;
use Xaraya\Services\xar;

final class UserGuiTest extends TestHelper
{
    protected function setUp(): void {}

    protected function tearDown(): void {}

    public function testUserGui(): void
    {
        $expected = UserGui::class;
        /** @var UserGui $usergui */
        $usergui = xar::mod()->usergui('apischemas');
        $this->assertEquals($expected, $usergui::class);
    }

    public function testUserMain(): void
    {
        $context = $this->createContext();
        /** @var UserGui $usergui */
        $usergui = xar::mod()->usergui('apischemas');
        $usergui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $usergui->main($args);

        $expected = [
            'context' => $context,
            'args' => $args,
            'module' => 'apischemas',
            'itemtype' => 0,
        ];
        $this->assertEquals($expected, $data);
    }

    public function testGuiFunc(): void
    {
        // initialize modules
        //xar::mod()->init();
        // needed to initialize the template cache
        xar::tpl()->init();
        $expected = 'View API Schemas';
        $output = (string) xar::mod()->guiFunc('apischemas');
        $this->assertStringContainsString($expected, $output);
    }
}
