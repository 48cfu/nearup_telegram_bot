<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Settings\Config;
use Near\NearData;

include_once __DIR__ . '/../bot.php';

class DeleteKeyCommand extends UserCommand
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
        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        if (!Common::ValidateAccess($chat_id, $message->getMessageId(), $user_id))
            return false;

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
                if ($text === '') {
                    $nearLogin = NearData::GetUserLogin($pdo, $user_id);
                    if (!$nearLogin) {
                        $data['text'] = "You didn't authorize current telegram account with the NEAR account. Please click /login to proceed";
                        Request::sendMessage($data);
                        $this->conversation->stop();
                    } else {
                        $notes['near_account_id'] = $nearLogin;

                        $accountData = NearData:: GetAccountAccessKeys($nearLogin);
                        $keys = [];

                        if (!$accountData["result"]["keys"]) {
                            $reply = "You account $nearLogin is locked";
                            $this->conversation->stop();
                        } else {
                            $output = [];
                            $nearPublicKey = NearData::GetPublicKey($pdo, $user_id);
                            $notes["near_public_key"] =  $nearPublicKey;
                            $this->conversation->update();
                            for ($i = 0; $i < count($accountData["result"]["keys"]); $i++) {
                                $key = $accountData["result"]["keys"][$i];
                                $string =  ($i + 1) . " " . $key["public_key"];
                                if ($nearPublicKey === $key["public_key"])
                                    $string .= " (Telegram bot key)";
                                $output[] = $string;
                                $keys[] = $key["public_key"];
                            }
                            $data['text'] = "Your current access keys:" . PHP_EOL . join(chr(10), $output);
                            Request::sendMessage($data);
                            $reply = "Please send a public key or index of the key from the list above to delete from account $nearLogin";
                        }

                        $notes['keys'] = $keys;
                        $data['text'] = $reply;
                        Request::sendMessage($data);

                        $notes['state'] = 0;
                        $this->conversation->update();
                    }

                    break;
                }

                $notes['public_key_or_index'] = $text;
                $text = '';

            case 1:
                if ($text === '' || !in_array($text, ['YES', 'NO'], true)) {

                    if ($notes['public_key_or_index']) {
                        $index = intval($notes['public_key_or_index']);
                        if ($index > 0 && isset($notes['keys']) && is_array($notes['keys']) && $index <= count($notes['keys']))
                            $notes['public_key'] = $notes['keys'][$index-1];
                        else if (in_array($notes['public_key_or_index'], $notes['keys']))
                            $notes['public_key'] = $notes['public_key_or_index'];
                        else {
                            $data['text'] = "Invalid key";
                            $this->conversation->stop();
                            Request::sendMessage($data);
                            break;
                        }

                        $notes['state'] = 1;
                        $this->conversation->update();
                        $data['text'] = "Are you ready to remove the key " . $notes['public_key'] . " from account " . $notes['near_account_id'] . "?";

                        if($notes['public_key'] === $notes['near_public_key'])
                            $data['text'] .= PHP_EOL."This will also logout telegram bot from your account";

                        $data['reply_markup'] = (new Keyboard(['YES', 'NO']))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        Request::sendMessage($data);

                        break;
                    }
                }

                $notes['confirm'] = $text;
                $text = '';

            case 2:
                if ($text === '') {

                    if($notes['confirm'] === "YES") {
                        $nearPrivateKey = NearData::GetPrivateKey($pdo, $user_id);
                        $nearAccount = $notes['near_account_id'];
                        $publicKey = $notes['public_key'];
                        if ($publicKey && $nearPrivateKey && $nearAccount) {
                            $reply = shell_exec("cd " . Config::$nodejs_folder . "; node deleteKey.js $nearAccount $nearPrivateKey $publicKey 2>&1");

                            if($publicKey === $notes['near_public_key']){
                                NearData:: saveUserDetails($pdo, $user_id, "", "", "");
                                $reply = PHP_EOL."NEAR account $nearAccount successfully removed from your current telegram account. Continue with the command /start";
                            }

                            $data['text'] = $reply;

                        } else
                            $data['text'] = "Wrong data";
                    }
                    else
                        $data['text'] = "Exit. Try again using command /deleteKey";

                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                    Request::sendMessage($data);
                    $this->conversation->stop();
                    break;
                }
        }

        return $result;
    }
}