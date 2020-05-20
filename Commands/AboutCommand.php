<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

include_once __DIR__ . '/../bot.php';

class AboutCommand extends UserCommand
{
    protected $name = 'start';
    protected $description = 'Welcome Screen';
    protected $usage = '/start';
    protected $version = '1.0.0';

    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user = $message->getFrom();
        $user_id = $user->getId();

        if(!Common::ValidateAccess($chat_id, $message->getMessageId(), $user_id))
            return false;

        $output = [
            "Near Shell Bot",
            "Fill free to contribute: https://github.com/zavodil/nearup_telegram_bot"
        ];
        $data = [
            'chat_id' => $chat_id,
            'text' => join(chr(10), $output),
        ];

        return Request::sendMessage($data);
    }
}