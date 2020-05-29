<?php

namespace Longman\TelegramBot\Commands\UserCommands;

include_once __DIR__ . '/../bot.php';

use Longman\TelegramBot\Request;
use Near\NearData;

class StartCommand extends MyCommand
{
    protected $name = 'start';
    protected $description = 'Welcome Screen';
    protected $usage = '/start';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $pdo = NearData::InitializeDB();
        $userData = NearData::GetUserData($pdo, $this->user_id);
        $nearLogin = null;
        if (count($userData)) {
            $userData = $userData[0];
            $nearLogin = $userData["near_account"];
            $nearNode = $userData["node_account"] ? (". {$this->strings["node"]}: `{$userData["node_account"]}`") : "";
            if ($userData["node_alarm_sent"])
                $nearNode .= "⚠";
        }

        $menu[] = $this->strings["title"];
        if ($nearLogin) {
            $menu[] = "*{$this->strings["walletInfo"]}*:";
            $menu[] = "{$this->strings["currentNearAccount"]}: `$nearLogin`" . $nearNode;
            $menu[] = "/checkBalance - " . $this->strings["checkBalance"];
            $menu[] = "/delegate - " . $this->strings["delegate"];
            $menu[] = "/send - " . $this->strings["send"];
            $menu[] = "/sendTelegram - " . $this->strings["sendTelegram"];
            $menu[] = "/deleteKey - " . $this->strings["deleteKey"];
            $menu[] = "/logout - " . $this->strings["logout"];
        } else
            $menu[] = "/login - " . $this->strings["login"];

        $menu = array_merge($menu, [
            "*{$this->strings["validatorOperations"]}*",
            "/addNode - " . $this->strings["addNode"],
            "/seatPrice - " . $this->strings["minimalStakeValidator"],
            "/currentValidators - " . $this->strings["currentValidators"],
            "/nextValidators - " . $this->strings["nextValidators"],
            "/currentProposals - " . $this->strings["currentProposals"],
            "/getKickouts - " . $this->strings["previousEpochKickouts"],
            "/convert - " . $this->strings["convertNEARyoctoNEAR"],
            "*{$this->strings["blockchainData"]}*",
            "/viewAccount username - " . $this->strings["accountData"],
            "/viewAccessKey username - " . $this->strings["accessKeysList"],
            "/about - " . $this->strings["aboutBot"]
        ]);

        // "/CurrentFishermen - CurrentFishermen",
        // "/NextFishermen - Next Fishermen",

        $data = [
            'chat_id' => $this->chat_id,
            'text' => join(chr(10), $menu),
            'parse_mode' => 'markdown',
        ];

        return Request::sendMessage($data);
    }
}