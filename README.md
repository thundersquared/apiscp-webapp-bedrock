# Bedrock WordPress Web App

This is a Bedrock WordPress application for [ApisCP](https://apiscp.com).

## Installation

```bash
cd /usr/local/apnscp
git clone https://github.com/thundersquared/apiscp-webapp-bedrock config/custom/webapps/bedrock
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

## Learning more
All third-party documentation is available via [docs.apiscp.com](https://docs.apiscp.com/admin/webapps/Custom/).
