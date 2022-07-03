<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps\Bedrock;

use Module\Support\Webapps\ComposerWrapper;
use Module\Support\Webapps\DatabaseGenerator;
use Module\Support\Webapps\Traits\PublicRelocatable;
use Opcenter\Auth\Password;
use Opcenter\Versioning;
use sqrd\ApisCP\Webapps\Bedrock\Helpers\File;

class Bedrock_Module extends \Wordpress_Module
{
    use PublicRelocatable
    {
        getAppRoot as getAppRootReal;
    }

    public const APP_NAME = 'Bedrock';
    public const PACKAGIST_NAME = 'roots/bedrock';
    public const DOTENV_COMMAND = 'aaemnnosttv/wp-cli-dotenv-command:^2.1';
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
     * Restrict version semantically
     *
     * @param string $lockType
     * @param string $version
     * @return string
     */
    protected function parseLock(string $lockType, string $version): string
    {
        switch ($lockType)
        {
            case 'major':
                return '~' . Versioning::asMinor($version);
            case 'minor':
                return Versioning::asMinor($version) . '.*';
            case 'patch':
                return $version;
            case '':
                return '>' . $version;
            default:
                warn("unknown lock type `%s' - restricting to `%s'", $lockType, $version);

                return $version;
        }
    }

    protected function execComposer(string $path = null, string $cmd, array $args = array()): array
    {
        return ComposerWrapper::instantiateContexted($this->getAuthContext())->exec($path, $cmd, $args);
    }

    protected function generateNewConfiguration(string $domain, string $docroot, DatabaseGenerator $dbcredentials, array $ftpcredentials = array()): bool
    {
        $approot = $this->getAppRoot($domain, $docroot);

        $steps = [
            'generate new salts' => ["dotenv salts generate", []],
            'set WP_HOME' => ["dotenv set WP_HOME '%(domain)s'", ['domain' => $domain]],
            'set DB_NAME' => ["dotenv set DB_NAME '%(name)s'", ['name' => $dbcredentials->database]],
            'set DB_USER' => ["dotenv set DB_USER '%(user)s'", ['user' => $dbcredentials->username]],
            'set DB_PASSWORD' => ["dotenv set DB_PASSWORD '%(password)s'", ['password' => $dbcredentials->password]],
        ];

        foreach ($steps as $name => $actions)
        {
            $ret = $this->execCommand($approot, $actions[0], $actions[1]);
            if (!$ret['success'])
            {
                return error('failed to %s, error: %s', $name, coalesce($ret['stderr'], $ret['stdout']));
            }
        }

        return true;
    }

    /**
     * Install Bedrock
     *
     * @param string $hostname domain or subdomain to install Bedrock
     * @param string $path optional path under hostname
     * @param array $opts additional install options
     * @return bool
     */
    public function install(string $hostname, string $path = '', array $opts = array()): bool
    {
        if (!$this->mysql_enabled())
        {
            return error('%(what)s must be enabled to install %(app)s',
                ['what' => 'MySQL', 'app' => static::APP_NAME]);
        }

        if (!$this->php_composer_exists())
        {
            return error('composer missing! contact sysadmin');
        }

        // Same situation as with Ghost. We can't install under a path for fear of
        // leaking information
        if ($path)
        {
            return error('Composer projects may only be installed directly on a subdomain or domain without a child path, e.g. https://domain.com but not https://domain.com/laravel');
        }

        if (!($docroot = $this->getDocumentRoot($hostname, $path)))
        {
            return error("failed to normalize path for `%s'", $hostname);
        }

        if (!$this->parseInstallOptions($opts, $hostname, $path))
        {
            return false;
        }

        // Install dotenv command
        $ret = $this->execCommand($docroot, 'package install %(dotenvcmd)s ', [
            'dotenvcmd' => static::DOTENV_COMMAND,
        ]);
        if (!$ret['success'])
        {
            return error('failed to install dotenv command, error: %s', coalesce($ret['stderr'], $ret['stdout']));
        }

        // Create Bedrock project with specified version
        $lock = $this->parseLock($opts['verlock'], $opts['version']);
        $ret = $this->execComposer($docroot, 'create-project --prefer-dist %(package)s %(docroot)s \'%(version)s\'', [
            'package' => static::PACKAGIST_NAME,
            'docroot' => $docroot,
            'version' => $lock,
        ]);

        // Rollback on failure
        if (!$ret['success'])
        {
            $this->file_delete($docroot, true);

            return error('failed to download roots/bedrock package: %s %s',
                $ret['stderr'], $ret['stdout']
            );
        }

        // Create new database
        $dbCred = DatabaseGenerator::mysql($this->getAuthContext(), $hostname);
        if (!$dbCred->create())
        {
            return false;
        }

        // Fill in .env file
        if (!$this->generateNewConfiguration($hostname, $docroot, $dbCred))
        {
            info('removing temporary files');
            if (!array_get($opts, 'hold'))
            {
                $this->file_delete($docroot, true);
                $dbCred->rollback();
            }
            return false;
        }

        if (!isset($opts['title']))
        {
            $opts['title'] = 'A Random Blog for a Random Reason';
        }

        if (!isset($opts['password']))
        {
            $opts['password'] = Password::generate();
            info("autogenerated password `%s'", $opts['password']);
        }

        info("setting admin user to `%s'", $this->username);
        // fix situations when installed on global subdomain
        $fqdn = $this->web_normalize_hostname($hostname);
        $opts['url'] = rtrim($fqdn . '/' . $path, '/');
        $args = array(
            'email' => $opts['email'],
            'mode' => 'install',
            'url' => $opts['url'],
            'title' => $opts['title'],
            'user' => $opts['user'],
            'password' => $opts['password'],
            'proto' => !empty($opts['ssl']) ? 'https://' : 'http://',
            'mysqli81' => 'function_exists("mysqli_report") && mysqli_report(0);'
        );
        $ret = $this->execCommand($docroot, 'core %(mode)s --admin_email=%(email)s --skip-email ' .
            '--url=%(proto)s%(url)s --title=%(title)s --admin_user=%(user)s --exec=%(mysqli81)s ' .
            '--admin_password=%(password)s', $args);
        if (!$ret['success'])
        {
            if (!array_get($opts, 'hold'))
            {
                $dbCred->rollback();
            }
            return error('failed to create database structure: %s', coalesce($ret['stderr'], $ret['stdout']));
        }

        $wpcli = Wpcli::instantiateContexted($this->getAuthContext());
        $wpcli->setConfiguration(['apache_modules' => ['mod_rewrite']]);

        $ret = $this->execCommand($docroot, "rewrite structure --hard '/%%postname%%/'");
        if (!$ret['success'])
        {
            return error('failed to set rewrite structure, error: %s', coalesce($ret['stderr'], $ret['stdout']));
        }

        // Remap public to web dir instead of app dir
        if (null === ($docroot = $this->remapPublic($hostname, $path, 'web/')))
        {
            $this->file_delete($docroot, true);

            return error("Failed to remap Bedrock to web/, manually remap from `%s' - Bedrock setup is incomplete!",
                $docroot);
        }

        return true;
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
        $versions = array_reverse(array_column($versions, 'version'));
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
        // App root is needed to use internal calls
        $approot = $this->getAppRoot($hostname, $path);

        // Read .env value
        $ret = $this->execCommand($approot, "dotenv get WP_ENV");

        if (!$ret['success'])
        {
            return error('failed to read env: %s', coalesce($ret['stderr'], $ret['stdout']));
        }

        return trim($ret['stdout']);
    }

    public function set_environment(string $hostname, string $path = '', string $environment = 'development˙'): ?bool
    {
        // App root is needed to use internal calls
        $approot = $this->getAppRoot($hostname, $path);

        // Replace .env value
        $ret = $this->execCommand($approot, "dotenv set WP_ENV %(environment)s", [
            'environment' => $environment,
        ]);

        if (!$ret['success'])
        {
            return error('failed to update env: %s', coalesce($ret['stderr'], $ret['stdout']));
        }

        return true;
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

    /**
     * Update core, plugins, and themes atomically
     *
     * @param string $hostname subdomain or domain
     * @param string $path optional path under hostname
     * @param string $version
     * @return bool
     */
    public function update_all(string $hostname, string $path = '', string $version = null): bool
    {
        return parent::update_all($hostname, $path, $version);
    }
}
