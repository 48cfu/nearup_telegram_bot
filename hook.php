<?php
require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/settings.php';

use Settings\Config;

try {
    $telegram = new Longman\TelegramBot\Telegram(Config::$bot_api_key, Config::$bot_username);

    $commands_paths = [
        __DIR__ . '/Commands',
    ];

    $telegram->addCommandsPaths($commands_paths);

    $telegram->enableMySql(Config::$mysql_credentials);

    //$telegram->enableLimiter();

    $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Silence is golden!
    // log telegram errors
    // echo $e->getMessage();
}