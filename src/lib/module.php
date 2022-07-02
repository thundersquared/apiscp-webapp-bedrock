<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps\Bedrock;

use Module\Support\Webapps\Traits\PublicRelocatable;
use sqrd\ApisCP\Webapps\Bedrock\Helpers\File;

class Bedrock_Module extends \Wordpress_Module
{
    use PublicRelocatable
    {
        getAppRoot as getAppRootReal;
    }

    public const APP_NAME = 'Bedrock';
    public const VERSION_CHECK_URL = 'https://repo.packagist.org/p2/roots/bedrock.json';

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
     * Install WordPress
     *
     * @param string $hostname domain or subdomain to install WordPress
     * @param string $path optional path under hostname
     * @param array $opts additional install options
     * @return bool
     */
    public function install(string $hostname, string $path = '', array $opts = array()): bool
    {
        return false;
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
        $key = 'bedrock.versions';

        // Attempt to retrieve cached versions
        $cache = \Cache_Super_Global::spawn();
        if (false !== ($ver = $cache->get($key)))
        {
            return $ver;
        }

        // Retrieve package information for version check
        $url = self::VERSION_CHECK_URL;
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $contents = file_get_contents($url, false, $context);
        if (!$contents)
        {
            return array();
        }
        $versions = json_decode($contents, true);

        // Cleanup before storage
        $versions = array_pop($versions['packages']);
        $versions = array_column($versions, 'version');
        $cache->set($key, $versions, 43200);

        return $versions;
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
        if (!$this->valid($hostname, $path))
        {
            return null;
        }

        $approot = $this->getAppRootPath($hostname, $path);

        // is composer.json file missing?
        if (!file_exists($approot . '/composer.json'))
        {
            return null;
        }

        return File::read_json($approot . '/composer.json', 'require.roots/wordpress');
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

        // Return current WP_ENV value
        return $variables['WP_ENV'];
    }

    public function set_environment(string $hostname, string $path = '', string $environment = 'development˙'): ?bool
    {
        // App root is needed to use internal calls
        $approot = $this->getAppRoot($hostname, $path);

        // Replace .env value
        $ret = $this->pman_run('sed -i \'s/^WP_ENV=.*$/WP_ENV=%(environment)s/g\' %(approot)s', [
            'environment' => $environment,
            'approot' => $approot . '/.env',
        ]);

        return $ret['success'] ? true : error('Failed to update env: %s', $ret['stderr']);
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
        return array_map(function ($environment) use ($active_environment) {
            $name = pathinfo($environment['filename'], PATHINFO_FILENAME);

            return [
                'name' => $name,
                'status' => $name === $active_environment,
            ];
        }, $environments);
    }
}
