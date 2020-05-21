<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Near\NearData;

include_once __DIR__ . '/../bot.php';
include_once __DIR__ . '/../near.php';

class ViewAccountCommand extends UserCommand
{
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user = $message->getFrom();
        $user_id = $user->getId();

        if (!Common::ValidateAccess($chat_id, $message->getMessageId(), $user_id))
            return false;

        $paramPosition = strpos($message->text, " ");
        if ($paramPosition > -1) {
            $account = substr($message->text, $paramPosition + 1);
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
                    $reply = join(chr(10), $output);
                }
            }
        } else
            $reply = "Account now found. Usage: /ViewAccount username";

        $data = [
            'chat_id' => $chat_id,
            'text' => $reply,
        ];

        return Request::sendMessage($data);
    }
}