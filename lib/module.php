<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps;

use Module\Support\Webapps\Traits\PublicRelocatable;

class Bedrock_Module extends \Wordpress_Module
{
	use PublicRelocatable
	{
		getAppRoot as getAppRootReal;
	}

	const APP_NAME = 'Bedrock WordPress';

	protected function getAppRoot(string $hostname, string $path = ''): ?string
	{
		if (file_exists($tmp = $this->getDocumentRoot($hostname, $path) . '/wp-config.php'))
		{
			return $tmp;
		}

		return $this->getAppRootReal($hostname, $path);
	}


	/**
	 * Get available versions
	 *
	 * Used to determine whether an app is eligible for updates
	 *
	 * @return array|string[]
	 */
	public function get_versions(): array
	{
		return ['1.0'];
	}

	/**
	 * Location is a valid Bedrock install
	 *
	 * @param string $hostname or $docroot
	 * @param string $path
	 * @return bool
	 */
	public function valid(string $hostname, string $path = ''): bool
	{
		if ($hostname[0] === '/')
		{
			if (!($path = realpath($this->domain_fs_path($hostname))))
			{
				return false;
			}
			$approot = \dirname($path);
		}
		else
		{
			$approot = $this->getAppRoot($hostname, $path);
			if (!$approot)
			{
				return false;
			}
			$approot = $this->domain_fs_path($approot);
		}

		return file_exists($approot . '/config/application.php') &&
			is_dir($approot . '/config/environments') &&
			is_dir($approot . '/web/app/plugins');
	}
}
