<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Settings\Config;
use Near\NearData;

class LoginCommand extends UserCommand
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
        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $data = [
            'chat_id' => $chat_id,
        ];


        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

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
                if ($text === '') {
                    $notes['state'] = 0;

                    $nearLogin = NearData::GetUserLogin($pdo, $user_id);
                    if($nearLogin){
                        $data['text'] = "You already authorized current telegram account with the NEAR account $nearLogin";
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

                    $data['text'] = "Please authorize this bot in your NEAR account by the URL: " . $url;
                    Request::sendMessage($data);
                    $data['text'] = 'Which account did you use?';
                    $result = Request::sendMessage($data);

                    break;
                }

                $notes['account'] = $text;
                $text = '';

            // no break
            case 1:
                if ($text === '') {

                    $account = $notes['account'];
                    $accountData = NearData:: GetAccountAccessKeys($account);
                    if (isset($accountData["error"])) {
                        $data['text'] = $accountData["error"]["message"] . " " . $accountData["error"]["data"];
                    } else {
                        $successFlag = false;
                        foreach ($accountData["result"]["keys"] as $key) {
                            if ($key["public_key"] === $notes['public']) {
                                $data['text'] = "You associated current telegram account with the NEAR account $account. Now you can /send NEAR tokens.";

                                NearData::saveUserDetails($pdo, $user_id, $notes['account'], $notes['public'], $notes['private']);
                                $successFlag = true;
                                break;
                            }
                        }

                        if (!$successFlag) {
                            $data['text'] = "You didn't authorize this bot to work with account $account";
                            Request::sendMessage($data);
                            $data['text'] = "Please try again by clicking /login ";
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