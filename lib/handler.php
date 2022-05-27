<?php

declare(strict_types=1);

namespace sqrd\ApisCP\Webapps;

use Module\Support\Webapps\App\Type\Wordpress\Handler as Wordpress_Handler;
use Module\Support\Webapps\Traits\PublicRelocatable;


class Bedrock_Handler extends Wordpress_Handler
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

	public function getAppFamily(): ?string
	{
		return 'bedrock-wordpress';
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
