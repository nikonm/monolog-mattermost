# Monolog Mattermost Handler

[![Build Status](https://travis-ci.com/nikonm/monolog-mattermost.svg?branch=master)](https://travis-ci.org/nikonm/monolog-mattermost)
[![Latest Stable Version](https://poser.pugx.org/nikonm/monolog-mattermost/v/stable)](https://packagist.org/packages/nikonm/monolog-mattermost)
[![Total Downloads](https://poser.pugx.org/nikonm/monolog-mattermost/downloads)](https://packagist.org/packages/nikonm/monolog-mattermost)

This Handler may be useful if you need send log messages to Mattermost channels, it's very easy to setup and integrate in your application if you are using Monolog.

Sample
```php
<?php

use Monolog\Logger;
use NikonM\Monolog\MattermostWebhookHandler;

$url = 'ssl://mattermost.yourcompany.com:443/hooks/<your-hook-token>';//PORT is Required
$options = [
    'username' => 'logger-user',
    'icon_url' => 'http://icons-for-free.com/icon/download-alien_icon-367307.png'
];

// Create the logger
$logger = new Logger('my_logger');
$logger->pushHandler(new MattermostWebhookHandler($url, $options, Logger::DEBUG));

// You can now use your logger
$logger->info('My logger is now ready');
```
