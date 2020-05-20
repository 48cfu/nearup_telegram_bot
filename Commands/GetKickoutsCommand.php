<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Near\NearData;
use Settings\Config;
use Near\NearView;

class GetKickoutsCommand extends UserCommand
{
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $kickouts = shell_exec("cd " . Config::$nodejs_folder . "; node getKickouts.js 2>&1");
        $kickouts = json_decode($kickouts, true);
        $output[] = "Previous epoch kickouts:";
        foreach ($kickouts as $validator) {
            $reply = $validator["account_id"].": ";

            $reason = $validator["reason"];
            if(isset($reason["NotEnoughBlocks"])){
                $reply .= "Not Enough Blocks. Produced ".$reason["NotEnoughBlocks"]["produced"]."/".$reason["NotEnoughBlocks"]["expected"];
            }
            else if(isset($reason["NotEnoughStake"])){
                $reply .=  "Not Enough Stake."; //Stake ". NearData::RoundNearBalance($reason["NotEnoughStake"]["stake_u128"]).PHP_EOL;
            }
            $output[] = $reply;
        }

        $data = [
            'chat_id' => $chat_id,
            'text' => join(chr(10), $output),
        ];

        return Request::sendMessage($data);
    }
}