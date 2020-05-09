<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Near\Near;
include_once __DIR__ . '/../near.php';


class ValidatorsCommand extends UserCommand
{
    protected $name = 'validators';                      // Your command's name
    protected $description = 'Get Current Validators List'; // Your command description
    protected $usage = '/validators';                    // Usage of your command
    protected $version = '1.0.0';                  // Version of your command

    public function execute()
    {
        $message = $this->getMessage();            // Get Message object

        $chat_id = $message->getChat()->getId();   // Get the current Chat ID
		
		$validatorsData = Near::GetNearRpcData("validators");
		
		$currentValidators = $validatorsData['result']['current_validators'];
		usort($currentValidators, function ($a, $b) {
			return strcmp($a["account_id"], $b["account_id"]);
		});

		$reply = "Current Betanet validators:".chr(10);
		$i = 1;
		$alertFound = false;
		
		foreach($currentValidators as $validator){
			$alert =  "";
			if($validator["num_produced_blocks"] < ($validator["num_expected_blocks"] * 0.9)){
				$alert =  " ⚠️ (".$validator["num_produced_blocks"]."/".$validator["num_expected_blocks"].")";
				$alertFound = true;
			}
			$reply .= (str_pad($i++, 2, "0", STR_PAD_LEFT)).". ".$validator["account_id"].": ".(round($validator["stake"]/1000000000000000000000000))." NEAR".$alert.chr(10);
		}
		
		if($alertFound)
			$reply .= "Legend: ⚠️ - kickout risk (produced blocks / expected blocks)";

        $data = [                                 
            'chat_id' => $chat_id,                 
            'text'    => $reply, 
        ];

        return Request::sendMessage($data);      
    }
}