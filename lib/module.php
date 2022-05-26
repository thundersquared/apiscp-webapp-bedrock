<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps;

require_once 'vendor/autoload.php';

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

	protected function getAppRootPath(string $hostname, string $path = ''): ?string
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
		$approot = getAppRootPath($hostname, $path);

		return false !== $approot &&
			file_exists($approot . '/config/application.php') &&
			is_dir($approot . '/config/environments') &&
			is_dir($approot . '/web/app/plugins');
	}

	public function get_version(string $hostname, string $path = ''): ?string
	{
		$approot = $this->getAppRootPath($hostname, $path);

		// is composer.json file missing?
		if (!file_exists($approot . '/composer.json'))
		{
			return null;
		}

		return self::read_json($approot . '/composer.json', 'require.roots/wordpress');
	}

	static public function read_json(string $path, $property = null)
	{
		$data = null;

		$contents = silence(static function () use ($path)
		{
			return file_get_contents($path);
		});

		if (false !== $contents)
		{
			$data = (array)json_decode($contents, true);

			if (!is_null($property))
			{
				$dot = new \Adbar\Dot($data);
				$data = $dot->get($property, null);
			}
		}

		return $data;
	}
}
