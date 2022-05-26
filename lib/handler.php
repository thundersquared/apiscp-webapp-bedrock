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

	public function getClassMapping(): string
	{
		// class ref is "bedrock"
		return 'bedrock';
	}

	public function display(): bool
	{
		return true;
	}

	public function getAppRoot($hostname, $path = ''): ?string
	{
		if (is_null($hostname))
		{
			$hostname = $this->getHostname();
			$path = $this->getPath();
		}

		if (file_exists($tmp = $this->getDocumentRoot($hostname, $path) . '/wp-config.php'))
		{
			return $tmp;
		}

		return $this->getAppRootReal($hostname, $path);
	}

	public function getVersions(): array
	{
		return ['1.0'];
	}

	public function getInstallableVersions(): array

	{
		return $this->getVersions();
	}

	public function detect($hostname, $path = ''): bool
	{
		return file_exists($this->getAppRoot($hostname, $path) . '/config/application.php') &&
			is_dir($this->getAppRoot($hostname, $path) . '/config/environments') &&
			is_dir($this->getAppRoot($hostname, $path) . '/web/app/plugins');
	}
}
