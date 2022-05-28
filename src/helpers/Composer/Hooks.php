<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps\Bedrock\Helpers\Composer;

class Hooks
{
    // Post install/update hook
    static public function postUpdate(): void
    {
        // Fetch current file path
        $current_directory = dirname(__FILE__);

        // Unlink Composer autoloaders
        unlink(sprintf('../../vendor/composer/InstalledVersions.php', $current_directory));

        // Link module to lib folder
        link(sprintf('../module', $current_directory), sprintf('../../lib', $current_directory));
    }
}
