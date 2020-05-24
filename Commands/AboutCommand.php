<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Bot\Common;
use Longman\TelegramBot\Request;

include_once __DIR__ . '/../bot.php';
include_once __DIR__ . '/../command.php';

class AboutCommand extends MyCommand
{
    protected $name = 'about';
    protected $description = 'About the bot';
    protected $usage = '/about';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();

        $text = $this->GetText();

        if (!$this->ValidateAccess())
            return false;

        $output = [
            $text['title'],
            $text['pleaseContribute']
        ];
        $data = [
            'chat_id' => $this->chat_id,
            'text' => $this->GenerateOutput($output),
        ];

        return Request::sendMessage($data);
    }
}