<?php

/**
 * API Schemas version information
 *
 * @package modules\apischemas
 * @copyright (C) 2020 Mike's Pub
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 * @author mikespub
 */

namespace Xaraya\Modules\ApiSchemas;

class Version
{
    /**
     * Get module version information
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'name' => 'apischemas',
            'id' => '18252',
            'version' => '2.5.3',
            'displayname' => 'API Schemas',
            'description' => 'Play with API schemas',
            'credits' => '',
            'help' => '',
            'changelog' => '',
            'license' => '',
            'official' => false,
            'author' => 'mikespub',
            'contact' => 'https://github.com/mikespub/xaraya-modules',
            'admin' => false,
            'user' => true,
            'class' => 'Utility',
            'category' => 'Miscellaneous',
            'namespace' => 'Xaraya\\Modules\\ApiSchemas',
            'securityschema'
             => [
             ],
            'dependency'
             => [
             ],
            'twigtemplates' => true,
            'dependencyinfo'
             => [
                 0
                  => [
                      'name' => 'Xaraya Core',
                      'version_ge' => '2.4.1',
                  ],
             ],
        ];
    }
}
