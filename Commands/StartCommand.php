<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

class StartCommand extends UserCommand
{
    protected $name = 'start';
    protected $description = 'Welcome Screen';
    protected $usage = '/start';
    protected $version = '1.0.0';

    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $menu = [
            "Near Shell Betanet Bot",
            "/ViewAccount username - Account data",
            "/CurrentValidators - Current Validators",
            "/NextValidators - Next Validators",
            "/CurrentProposals - Current Proposals",
            "/CurrentFishermen - CurrentFishermen",
            "/NextFishermen - Next Fishermen",
            "/about - About bot"
        ];
        $data = [
            'chat_id' => $chat_id,
            'text' => join(chr(10), $menu),
        ];

        return Request::sendMessage($data);
    }
}