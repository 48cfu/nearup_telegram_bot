<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Near\NearData;

include_once __DIR__ . '/../near.php';

class ViewAccessKeyCommand extends UserCommand
{
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $paramPosition = strpos($message->text, " ");
        if($paramPosition > -1) {
            $account = substr($message->text, $paramPosition + 1);
            if($account) {
                $accountData = NearData::GetNearRpcData("query", ["request_type" => "view_access_key_list", "finality" => "final", "account_id" => $account]);

                if(isset($accountData["error"]))
                    $reply = $accountData["error"]["message"]." ".$accountData["error"]["data"];
                else {
                    $output[] = "Account " . $account;
                    foreach($accountData["result"]["keys"] as $key){
                        $output[] = "- ".$key["public_key"]." (".$key["access_key"]["permission"].", nonce: ".$key["access_key"]["nonce"].")";
                    }

                    $reply = join(chr(10), $output);
                }
            }
        }
        else
            $reply = "Account now found. Usage: /ViewAccessKey username".$paramPosition;

        $data = [
            'chat_id' => $chat_id,
            'text' => $reply,
        ];

        return Request::sendMessage($data);
    }
}