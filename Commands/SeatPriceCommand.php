<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Settings\Config;

include_once __DIR__ . '/../bot.php';

class SeatPriceCommand extends UserCommand
{
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user = $message->getFrom();
        $user_id = $user->getId();

        if(!Common::ValidateAccess($chat_id, $message->getMessageId(), $user_id))
            return false;

        $price = shell_exec("cd " . Config::$nodejs_folder . "; node getSeatPrice.js 2>&1");
        $price = trim($price);

        $data = [
            'chat_id' => $chat_id,
            'text' => "Current Seat Price: $price NEAR"
        ];

        return Request::sendMessage($data);
    }
}