<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Request;
use Near\NearData;
use Near\NearView;

class TopValidatorsCommand extends MyCommand
{
    protected $name = 'topValidators';
    protected $description = 'Get Current Top Validators';
    protected $usage = '/topValidators';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $validatorsData = NearData::GetNearRpcData("validators");

        if(isset($validatorsData['error']))
            $reply = $validatorsData['error']['message'];
        else
            $reply = "🔝{$this->strings['title']} \n{$this->GenerateOutput(NearView::FormatValidators($validatorsData['result']['current_validators'],  $this->strings, "stake"))}";

        $data = [
            'chat_id' => $this->chat_id,
            'text' => $reply,
            'parse_mode' => 'markdown',
        ];

        return Request::sendMessage($data);
    }
}