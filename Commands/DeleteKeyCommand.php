<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Near\NearData;
use Settings\Config;

include_once __DIR__ . '/../bot.php';

class DeleteKeyCommand extends MyCommand
{
    protected $name = 'deleteKey';
    protected $description = 'Delete Access Key';
    protected $usage = '/deleteKey';
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
                if ($this->text === '') {
                    $nearLogin = NearData::GetUserLogin($pdo, $this->user_id);
                    if (!$nearLogin) {
                        $data['text'] = $this->strings["telegramAccountNotAuthorized"];
                        Request::sendMessage($data);
                        $this->conversation->stop();
                    } else {
                        $notes['near_account_id'] = $nearLogin;

                        $accountData = NearData:: GetAccountAccessKeys($nearLogin);
                        $keys = [];

                        if (!$accountData["result"]["keys"]) {
                            $reply = $this->GenerateOutput($this->strings["YourAccountLocked"], [$nearLogin]);
                            $this->conversation->stop();
                        } else {
                            $output = [];
                            $nearPublicKey = NearData::GetPublicKey($pdo, $this->user_id);
                            $notes["near_public_key"] = $nearPublicKey;
                            $this->conversation->update();
                            for ($i = 0; $i < count($accountData["result"]["keys"]); $i++) {
                                $key = $accountData["result"]["keys"][$i];
                                $string = ($i + 1) . " `{$key["public_key"]}` ";
                                if ($nearPublicKey === $key["public_key"])
                                    $string .= " *({$this->strings["telegramBotKey"]})*";
                                $output[] = $string;
                                $keys[] = $key["public_key"];
                            }
                            $data['text'] = $this->strings["yourCurrentAccessKeys"] . PHP_EOL . join(chr(10), $output);
                            $data['parse_mode'] = "markdown";
                            Request::sendMessage($data);
                            $reply = "{$this->strings["sendPublicKeyOrIndex"]} *$nearLogin*";
                        }

                        $notes['keys'] = $keys;
                        $data['text'] = $reply;
                        Request::sendMessage($data);

                        $notes['state'] = 0;
                        $this->conversation->update();
                    }

                    break;
                }

                $notes['public_key_or_index'] = $this->text;
                $this->text = '';

            case 1:
                if ($this->text === '' || !in_array($this->text, [$this->strings["YES"], $this->strings["NO"]], true)) {

                    if ($notes['public_key_or_index']) {
                        $index = intval($notes['public_key_or_index']);
                        if ($index > 0 && isset($notes['keys']) && is_array($notes['keys']) && $index <= count($notes['keys']))
                            $notes['public_key'] = $notes['keys'][$index - 1];
                        else if (in_array($notes['public_key_or_index'], $notes['keys']))
                            $notes['public_key'] = $notes['public_key_or_index'];
                        else {
                            $data['text'] = $this->strings["invalidKey"];
                            $this->conversation->stop();
                            Request::sendMessage($data);
                            break;
                        }

                        $notes['state'] = 1;
                        $this->conversation->update();
                        $data['text'] = $this->GenerateOutput($this->strings["areYouReadyToRemove"], ["`{$notes['public_key']}`",  "*{$notes['near_account_id']}*"]);

                        if ($notes['public_key'] === $notes['near_public_key'])
                            $data['text'] .= PHP_EOL . "{$this->strings["thisWillAlsoDeleteTelegram"]}.";

                        $data['reply_markup'] = (new Keyboard([$this->strings["YES"], $this->strings["NO"]]))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        $data['parse_mode'] = "markdown";
                        Request::sendMessage($data);

                        break;
                    }
                }

                $notes['confirm'] = $this->text;
                $this->text = '';

            case 2:
                if ($this->text === '') {
                    if ($notes['confirm'] === $this->strings["YES"]) {
                        $nearPrivateKey = NearData::GetPrivateKey($pdo, $this->user_id);
                        $nearAccount = $notes['near_account_id'];
                        $publicKey = $notes['public_key'];
                        if ($publicKey && $nearPrivateKey && $nearAccount) {
                            $reply = shell_exec("cd " . Config::$nodejs_folder . "; node deleteKey.js $nearAccount $nearPrivateKey $publicKey 2>&1");

                            if ($publicKey === $notes['near_public_key']) {
                                NearData:: saveUserDetails($pdo, $this->user_id, "", "", "");
                                $reply = PHP_EOL . $this->GenerateOutput($this->strings["nearAccountSuccessfullyRemoved"], [$nearAccount]);
                            }

                            $data['text'] = $reply;

                        } else
                            $data['text'] = $this->strings["wrongData"];
                    } else
                        $data['text'] = $this->strings["exitTryAgain"];

                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                    Request::sendMessage($data);
                    $this->conversation->stop();
                    break;
                }
        }

        return $result;
    }
}