<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps;

use Module\Support\Webapps\Traits\PublicRelocatable;
use Dotenv\Environment\Adapter\ArrayAdapter;
use Dotenv\Environment\DotenvFactory;
use Dotenv\Dotenv;

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
				return null;
			}
			$approot = \dirname($path);
		}
		else
		{
			$approot = $this->getAppRoot($hostname, $path);
			if (!$approot)
			{
				return null;
			}
			$approot = $this->domain_fs_path($approot);
		}

		return $approot;
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
		$approot = $this->getAppRootPath($hostname, $path);

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

	public function get_environment(string $hostname, string $path = ''): ?string
	{
		$approot = $this->getAppRootPath($hostname, $path);

		// is .env file missing?
		if (!file_exists($approot . '/.env'))
		{
			return null;
		}

		// Create instance with no adapters besides ArrayAdapter,
		// we just need to peek at it without loading it actually
		$dotenv = Dotenv::create($approot, null, new DotenvFactory([new ArrayAdapter()]));
		$variables = $dotenv->load();

		// Reeturn current WP_ENV value
		return $variables['WP_ENV'];
	}

	public function get_environments(string $hostname, string $path = ''): ?array
	{
		// App root is needed to use internal calls
		$approot = $this->getAppRoot($hostname, $path);
		// App root path is needed for PHP direct checks
		$approotpath = $this->getAppRootPath($hostname, $path);

		// is config/environments/ dir missing?
		if (!is_dir($approotpath . '/config/environments/'))
		{
			return null;
		}

		// Get current active environment
		$active_environment = $this->get_environment($hostname, $path);

		// Scan config/environments/ to extract available environments
		$environments = $this->file_get_directory_contents($approot . '/config/environments/');

		// Collect and clean output checking whether environment matches active one
		return array_map(function ($environment) use ($active_environment)
		{
			$name = pathinfo($environment['filename'], PATHINFO_FILENAME);

			return [
				'name' => $name,
				'status' => $name === $active_environment,
			];
		}, $environments);
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
