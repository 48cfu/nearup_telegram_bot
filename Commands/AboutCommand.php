<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

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