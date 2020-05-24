<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Request;

class AboutCommand extends MyCommand
{
    protected $name = 'about';
    protected $description = 'About the bot';
    protected $usage = '/about';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $data = [
            'chat_id' => $this->chat_id,
            'text' => $this->GenerateOutput([
                $this->strings['title'],
                $this->strings['pleaseContribute']
            ]),
        ];

        return Request::sendMessage($data);
    }
}