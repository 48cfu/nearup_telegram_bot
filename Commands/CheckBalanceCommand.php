<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Near\NearData;
use Longman\TelegramBot\Request;

class CheckBalanceCommand extends MyCommand
{
    protected $name = 'checkBalance';
    protected $description = 'Check Balance';
    protected $usage = '/checkBalance';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $pdo = NearData::InitializeDB();
        $account = NearData::GetUserLogin($pdo, $this->user_id);

        if ($account) {
            $accountData = NearData::GetAccountBalance($account);

            if (isset($accountData["error"]))
                $reply = $accountData["error"]["message"] . " " . $accountData["error"]["data"];
            else {
                $output[] = "{$this->text['account']} *{%0%}*";
                $output[] = "{$this->text['balance']}: `{%1%} NEAR`";
                $output[] = "{$this->text['locked']}: `{%2%} NEAR`";
                $output[] = "{$this->text['storageUsage']}: `{%3%}`";
                $output[] = "{$this->text['accessKeysList']}: /ViewAccessKey\_{%4%}";

                $publicKey = NearData::GetPublicKey($pdo, $this->user_id);
                if ($publicKey)
                    $output[] = $this->text['associatedPublicKey'] . " `{%5%}`";

                $reply = $this->GenerateOutput($output, [
                    strtoupper($account),
                    NearData::RoundNearBalance($accountData["result"]["amount"]),
                    NearData::RoundNearBalance($accountData["result"]["locked"]),
                    NearData::RoundNearBalance($accountData["result"]["storage_usage"]),
                    $account,
                    $publicKey
                ]);
            }
        } else
            $reply = $this->text['accountNotFound'];

        $data = [
            'chat_id' => $this->chat_id,
            'text' => $reply,
            'parse_mode' => 'markdown',
        ];

        return Request::sendMessage($data);
    }
}