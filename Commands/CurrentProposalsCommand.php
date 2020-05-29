<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Request;
use Near\NearData;
use Near\NearView;

class CurrentProposalsCommand extends MyCommand
{
    protected $name = 'currentProposals';
    protected $description = 'Get Current Proposals';
    protected $usage = '/currentProposals';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $validatorsData = NearData::GetNearRpcData("validators");

        $reply = "{$this->strings['currentProposals']} \n{$this->GenerateOutput(NearView::FormatValidators($validatorsData['result']['current_proposals'],  $this->strings))}";

        $data = [
            'chat_id' => $this->chat_id,
            'text' => $reply,
            'parse_mode' => 'markdown',
        ];

        return Request::sendMessage($data);
    }
}