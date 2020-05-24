<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Request;
use Near\NearData;
use Near\NearView;

class NextValidatorsCommand extends MyCommand
{
    protected $name = 'nextValidators';
    protected $description = 'Get Next Validators';
    protected $usage = '/nextValidators';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $validatorsData = NearData::GetNearRpcData("validators");

        $reply = "{$this->strings['nextValidators']} \n {$this->GenerateOutput(NearView::FormatValidators($validatorsData['result']['next_validators'],  $this->strings))}";

        $data = [
            'chat_id' => $this->chat_id,
            'text' => $reply,
            'parse_mode' => 'markdown',
        ];

        return Request::sendMessage($data);
    }
}