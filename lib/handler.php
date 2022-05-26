<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps;

use Module\Support\Webapps\App\Type\Wordpress\Handler as Wordpress_Handler;
use Module\Support\Webapps\Traits\PublicRelocatable;


class Bedrock extends Wordpress_Handler
{
	use PublicRelocatable
	{
		getAppRoot as getAppRootReal;
	}

	const NAME = 'Bedrock WordPress';
	const ADMIN_PATH = null;
	const LINK = 'https://roots.io/bedrock/';

	const DEFAULT_FORTIFICATION = 'max';
	const FEAT_ALLOW_SSL = true;
	const FEAT_RECOVERY = false;

	public function display(): bool
	{
		return true;
	}

	public function getAppRoot(): ?string
	{
		return $this->getAppRootReal($this->getHostname(), $this->getPath());
	}

	public function getVersions(): array
	{
		return ['1.0'];
	}

	public function getInstallableVersions(): array

	{
		return $this->getVersions();
	}

	public function detect($mixed, $path = ''): bool
	{
		return file_exists($this->getAppRoot() . '/config/application.php') &&
			is_dir($this->getAppRoot() . '/config/environments') &&
			is_dir($this->getAppRoot() . '/web/app/plugins');
	}
}
