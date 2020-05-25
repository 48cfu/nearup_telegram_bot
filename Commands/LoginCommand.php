<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Near\NearData;
use Settings\Config;

class LoginCommand extends MyCommand
{
    protected $name = 'login';
    protected $description = 'Login to NEAR';
    protected $usage = '/login';
    protected $version = '1.0.0';
    protected $need_mysql = true;
    protected $private_only = true;

    protected $conversation;

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $data = ['chat_id' => $this->chat_id];


        if ($this->chat->isGroupChat() || $this->chat->isSuperGroup()) {
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        $this->conversation = new Conversation($this->user_id, $this->chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }

        $result = Request::emptyResponse();
        $pdo = NearData::InitializeDB();

        switch ($state) {
            case 0:
                $pair = null;
                if ($this->text === '') {
                    $notes['state'] = 0;

                    $nearLogin = NearData::GetUserLogin($pdo, $this->user_id);
                    if ($nearLogin) {
                        $data['text'] = "{$this->strings["alreadyAuthorized"]} $nearLogin";
                        Request::sendMessage($data);
                        $this->conversation->stop();
                        break;
                    }

                    $pair_json = shell_exec("cd " . Config::$nodejs_folder . "; node getKeyPair.js 2>&1");
                    $pair = json_decode($pair_json, true);

                    $url = "https://wallet.betanet.near.org/login/?title=NearUp+Bot&public_key=" . urlencode($pair['public']);

                    $notes['public'] = $pair["public"];
                    $notes['private'] = $pair['private'];
                    $notes['url'] = $url;

                    $this->conversation->update();

                    $data['text'] = self::GenerateOutput($this->strings["pleaseAuthorizeByUrl"], [$url]);
                    Request::sendMessage($data);
                    $data['text'] = $this->strings["whichAccountUsed"];
                    $result = Request::sendMessage($data);

                    break;
                }

                $notes['account'] = $this->text;
                $this->text = '';

            case 1:
                if ($this->text === '') {

                    $account = $notes['account'];
                    $accountData = NearData:: GetAccountAccessKeys($account);
                    if (isset($accountData["error"])) {
                        $data['text'] = $accountData["error"]["message"] . " " . $accountData["error"]["data"];
                    } else {
                        $successFlag = false;
                        foreach ($accountData["result"]["keys"] as $key) {
                            if ($key["public_key"] === $notes['public']) {
                                $data['text'] = self::GenerateOutput($this->strings["associatedCurrentTelegramWithNearAccount"], [$account]);

                                NearData::saveUserDetails($pdo, $this->user_id, $notes['account'], $notes['public'], $notes['private']);
                                $successFlag = true;
                                break;
                            }
                        }

                        if (!$successFlag) {
                            $data['text'] = "{$this->strings["youDidntAuthorize"]} $account";
                            Request::sendMessage($data);
                            $data['text'] = $this->strings["pleaseTryAgainByClickingLogin"];
                        }

                    }

                    $result = Request::sendMessage($data);
                    $this->conversation->stop();
                    break;
                }

        }

        return $result;
    }
}