<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Request;
use Near\NearData;
use Near\NearView;

class CurrentFishermenCommand extends MyCommand
{
    protected $name = 'currentFishermen';
    protected $description = 'Get Current Fishermen';
    protected $usage = '/currentFishermen';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $validatorsData = NearData::GetNearRpcData("validators");

        $reply = $this->strings['currentFishermen'] . PHP_EOL . $this->GenerateOutput(NearView::FormatValidators($validatorsData['result']['current_fishermen'],  $this->strings));

        $data = [
            'chat_id' => $this->chat_id,
            'text' => $reply,
            'parse_mode' => 'markdown'
        ];

        return Request::sendMessage($data);
    }
}