<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Near\NearData;
use Near\NearView;

include_once __DIR__ . '/../near.php';


class CurrentValidatorsCommand extends UserCommand
{
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $validatorsData = NearData::GetNearRpcData("validators");

        if(isset($validatorsData['error']))
            $reply = $validatorsData['error']['message'];
        else
            $reply = "Current Validators:" . chr(10) . NearView::FormatValidators($validatorsData['result']['current_validators']);

        $data = [
            'chat_id' => $chat_id,
            'text' => $reply,
        ];

        return Request::sendMessage($data);
    }
}