# Bedrock WordPress Web App

This is a Bedrock WordPress application for [ApisCP](https://apiscp.com).

## Installation

```bash
cd /usr/local/apnscp
sudo -u apnscp mkdir -p config/custom/webapps
sudo -u apnscp git clone https://github.com/thundersquared/apiscp-webapp-bedrock config/custom/webapps/bedrock
cd config/custom/webapps/bedrock
sudo -u apnscp composer install
sudo -u apnscp composer dump-autoload -o --no-dev
cd /usr/local/apnscp
./composer dump-autoload -o
```
Edit config/custom/boot.php, create if not exists:

```php
<?php
\a23r::registerModule('bedrock', \sqrd\ApisCP\Webapps\Bedrock_Module::class);
\Module\Support\Webapps::registerApplication('bedrock', \sqrd\ApisCP\Webapps\Bedrock::class);
```

Then restart ApisCP.

```bash
systemctl restart apiscp
```

Voila!

## Updating

```
cd /usr/local/apnscp/config/custom/webapps/bedrock
sudo -u apnscp git pull
sudo -u apnscp composer update
sudo -u apnscp composer dump-autoload -o --no-dev
cd /usr/local/apnscp
./composer dump-autoload -o && systemctl restart apiscp
```

## Learning more
All third-party documentation is available via [docs.apiscp.com](https://docs.apiscp.com/admin/webapps/Custom/).
