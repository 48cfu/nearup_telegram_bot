<?php

use Longman\TelegramBot\Request;
use Near\NearData;
use Settings\Config;

require_once __DIR__ ."/../near.php";
require_once __DIR__ ."/../settings.php";
require_once __DIR__ .'/../vendor/autoload.php';

$pdo = NearData::InitializeDB();
$users = NearData::GetUserNodes($pdo);

$maxBlocksShortage = 3;

$validatorsData = NearData::GetNearRpcData("validators");
if (isset($validatorsData["result"]) && isset($validatorsData["result"]["current_validators"])) {
    foreach ($validatorsData["result"]["current_validators"] as $validator) { // vise versa if users with nodes > 100
        foreach ($users as $user) {
            if ($user["node_account"] === $validator["account_id"]) {
                if ($validator["num_expected_blocks"] - $validator["num_produced_blocks"] > $maxBlocksShortage) {
                    if (!$user["node_alarm_sent"]) { // send alert
                        NearData::setNodeAlarm($pdo, $user["id"], 1);
                        if($user['language_code'] === "ru")
                            SendMessage($user["id"], "⚠ NEAR аккаунт `{$user['node_account']}` перестал производить блоки.
Ожидания/реальность: {$validator["num_expected_blocks"]}/{$validator["num_produced_blocks"]} блоков");
                        else
                            SendMessage($user["id"], "⚠ NEAR account `{$user['node_account']}` has troubles with blocks producing.
Expected/produced: {$validator["num_expected_blocks"]}/{$validator["num_produced_blocks"]} blocks");
                        echo "{$user["id"]} alarm\n";
                    }
                } else {
                    if ($user["node_alarm_sent"]) { // remove alert
                        NearData::setNodeAlarm($pdo, $user["id"], 0);
                    }
                }
            }
        }
    }
}

function SendMessage($user_id, $text)
{
    $telegram = new Longman\TelegramBot\Telegram(Config::$bot_api_key, Config::$bot_username);
    $telegram->enableMySql(Config::$mysql_credentials);

    $data = [
        'chat_id' => $user_id,
        'text' => $text,
        'parse_mode' => 'markdown',
    ];
    return Request::sendMessage($data);
}
