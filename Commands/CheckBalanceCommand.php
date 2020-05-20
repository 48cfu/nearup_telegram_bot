<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Near\NearData;

include_once __DIR__ . '/../bot.php';
include_once __DIR__ . '/../near.php';

class CheckBalanceCommand extends UserCommand
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
        $account = NearData::GetUserLogin($pdo, $user_id);

        if ($account) {
            $accountData = NearData::GetAccountBalance($account);

            if (isset($accountData["error"]))
                $reply = $accountData["error"]["message"] . " " . $accountData["error"]["data"];
            else {
                $output = [
                    "Account " . $account,
                    "Balance: " . NearData::RoundNearBalance($accountData["result"]["amount"]),
                    "Locked: " . NearData::RoundNearBalance($accountData["result"]["locked"]),
                    "Storage Usage: " . NearData::RoundNearBalance($accountData["result"]["storage_usage"]),
                    "Access Keys List: /ViewAccessKey " . $account
                ];

                $publicKey= NearData::GetPublicKey($pdo, $user_id);
                if($publicKey)
                    $output[] = "Associated Public key $publicKey";

                $reply = join(chr(10), $output);
            }
        } else
            $reply = "Account now found";

        $data = [
            'chat_id' => $chat_id,
            'text' => $reply,
        ];

        return Request::sendMessage($data);
    }
}