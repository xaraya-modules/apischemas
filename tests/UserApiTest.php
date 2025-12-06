<?php

namespace Xaraya\Modules\ApiSchemas\Tests;

use Xaraya\Modules\TestHelper;
use Xaraya\Modules\ApiSchemas\UserApi;
use Xaraya\Modules\ApiSchemas\Import;
use Xaraya\Services\xar;

final class UserApiTest extends TestHelper
{
    protected function setUp(): void {}

    protected function tearDown(): void {}

    public function testUserApi(): void
    {
        /** @var UserApi $userapi */
        $userapi = xar::mod()->userapi('apischemas');
        $itemtypes = $userapi->getItemTypes();

        $expected = 16;
        $this->assertCount($expected, $itemtypes);

        $expected = [];
        $expected[1] = [
            'objectid' => '119',
            'name' => 'api_films',
            'label' => 'Film',
            'title' => 'View Film',
            'url' => 'http:///index.php?object=api_films&amp;method=view',
        ];
        $this->assertEquals($expected[1], $itemtypes[1]);
    }

    public function testImport(): void
    {
        $result = true;
        $this->assertTrue($result);
    }
}
