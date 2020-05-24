<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Request;
use Near\NearData;
use Near\NearView;

class NextFishermenCommand extends MyCommand
{
    protected $name = 'nextFishermen';
    protected $description = 'Get Next Fishermen';
    protected $usage = '/nextFishermen';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;


        $validatorsData = NearData::GetNearRpcData("validators");

        $reply = $this->strings['nextFishermen'] . PHP_EOL . $this->GenerateOutput(NearView::FormatValidators($validatorsData['result']['next_fishermen'],  $this->strings));

        $data = [
            'chat_id' => $this->chat_id,
            'text' => $reply,
            'parse_mode' => 'markdown'
        ];

        return Request::sendMessage($data);
    }
}