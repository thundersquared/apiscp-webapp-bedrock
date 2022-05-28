<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps\Bedrock;

class ComposerHooks
{
    static public function postUpdate(): void
    {
        @unlink(sprintf('../../vendor/composer/InstalledVersions.php', dirname(__FILE__)));
    }
}
