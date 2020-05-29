<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Request;
use Settings\Config;

class GetKickoutsCommand extends MyCommand
{
    protected $name = 'getKickouts';
    protected $description = 'Get Kickouts';
    protected $usage = '/getKickouts';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $kickouts = shell_exec("cd " . Config::$nodejs_folder . "; node getKickouts.js 2>&1");
        $kickouts = json_decode($kickouts, true);
        $output[] = $this->strings['title'].":";
        foreach ($kickouts as $validator) {
            $reply = "*{$validator["account_id"]}*: ";

            $reason = $validator["reason"];
            if (isset($reason["NotEnoughBlocks"])) {
                $reply .= "{$this->strings["notEnoughBlocks"]}. {$this->strings["produced"]} `{$reason["NotEnoughBlocks"]["produced"]}/{$reason["NotEnoughBlocks"]["expected"]}`";
            } else if (isset($reason["NotEnoughStake"])) {
                $reply .= "{$this->strings['notEnoughStake']}.";
            } else {
                $reply .= $reason;
            }
            $output[] = $reply;
        }

        $data = [
            'chat_id' => $this->chat_id,
            'text' => $this->GenerateOutput($output),
            'parse_mode' => 'markdown'
        ];

        return Request::sendMessage($data);
    }
}