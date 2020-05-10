<?php
require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/settings.php';

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    // Enable MySQL
    $telegram->enableMySql($mysql_credentials);

    // Handle telegram getUpdates request
    $telegram->handleGetUpdates();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // log telegram errors
    // echo $e->getMessage();
}