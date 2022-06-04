<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps\Bedrock;

use Module\Support\Webapps\App\Type\Wordpress\Handler as Wordpress_Handler;
use Module\Support\Webapps\Traits\PublicRelocatable;


class Handler extends Wordpress_Handler
{
	use PublicRelocatable
	{
		getAppRoot as getAppRootReal;
	}

	public const NAME = 'Bedrock';
	public const ADMIN_PATH = null;
	public const LINK = 'https://roots.io/bedrock/';

	public const DEFAULT_FORTIFICATION = 'max';
	public const FEAT_ALLOW_SSL = true;
	public const FEAT_RECOVERY = false;

	public function getClassMapping(): string
	{
		// class ref is "bedrock"
		return 'bedrock';
	}

	public function getAppFamily(): ?string
	{
		return 'bedrock';
	}

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

	public function handle(array $params): bool
	{
		if (!empty($params['say']))
		{
			return $this->{$this->getClassMapping() . '_hello'}();
		}

		return parent::handle($params);
	}
}
