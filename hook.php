<?php
// Load composer
require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/settings.php';

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    $commands_paths = [
        __DIR__ . '/Commands',
    ];
    // Add this line inside the try{}
    $telegram->addCommandsPaths($commands_paths);

    // Handle telegram webhook request
    $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Silence is golden!
    // log telegram errors
    // echo $e->getMessage();
}