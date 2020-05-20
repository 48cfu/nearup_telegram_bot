<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Near\NearData;
use Near\NearView;

include_once __DIR__ . '/../bot.php';
include_once __DIR__ . '/../near.php';


class CurrentFishermenCommand extends UserCommand
{
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user = $message->getFrom();
        $user_id = $user->getId();

        if(!Common::ValidateAccess($chat_id, $message->getMessageId(), $user_id))
            return false;

        $validatorsData = NearData::GetNearRpcData("validators");

        $reply = "Current Fishermen:" . chr(10) . NearView::FormatValidators($validatorsData['result']['current_fishermen']);

        $data = [
            'chat_id' => $chat_id,
            'text' => $reply,
        ];

        return Request::sendMessage($data);
    }
}