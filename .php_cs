<?php

/*
 * In order to make it work, fabpot/php-cs-fixer must be installed globally with composer.
 *
 * @link https://github.com/Soullivaneuh/php-cs-fixer-styleci-bridge
 * @link https://github.com/FriendsOfPHP/PHP-CS-Fixer
 */

require_once __DIR__.'/vendor/sllh/php-cs-fixer-styleci-bridge/autoload.php';

use SLLH\StyleCIBridge\ConfigBridge;

return ConfigBridge::create();
