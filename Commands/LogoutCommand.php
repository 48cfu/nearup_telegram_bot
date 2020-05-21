<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Near\NearData;

include_once __DIR__ . '/../bot.php';

class LogoutCommand extends UserCommand
{
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user = $message->getFrom();
        $user_id = $user->getId();

        if(!Common::ValidateAccess($chat_id, $message->getMessageId(), $user_id))
            return false;

        $pdo = NearData::InitializeDB();
        $nearLogin = NearData::GetUserLogin($pdo, $user_id);

        $data = [
            'chat_id' => $chat_id,
        ];

        if(!$nearLogin)
            $data['text'] = "Login wasn't found";
        else{
            NearData:: saveUserDetails($pdo, $user_id, "", "", "");
            $data['text'] = "NEAR account $nearLogin successfully removed from your current telegram account";
        }

        return Request::sendMessage($data);
    }
}