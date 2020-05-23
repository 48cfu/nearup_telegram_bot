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

class DelegateCommand extends UserCommand
{
    protected $name = 'delegate';
    protected $description = 'Delegate to the Staking Pool';
    protected $usage = '/delegate';
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
                        $notes['nearAccountId'] = $nearLogin;
                        $notes['state'] = 0;
                        $this->conversation->update();

                        $data['text'] = "Please enter the name of your staking pool contract (for example: node)";
                        Request::sendMessage($data);
                    }

                    break;
                }

                $notes['recipient'] = $text;
                $text = '';

            case 1:
                if ($text === '') {

                    if ($notes['recipient']) {
                        $notes['state'] = 1;
                        $this->conversation->update();
                        $recipient = $notes['recipient'];
                        $data['text'] = "How many NEAR tokens do you want to delegate to the contract $recipient?";
                        Request::sendMessage($data);

                        break;
                    }
                }

                $notes['amount'] = $text;
                $text = '';

            case 2:
                if ($text === '') {

                    $nearPrivateKey = NearData::GetPrivateKey($pdo, $user_id);
                    $nearAccount = $notes['nearAccountId'];
                    $amount = $notes['amount'];
                    $recipient =  $notes['recipient'];
                    if (intval($amount) > 0 && $nearPrivateKey && $nearAccount) {
                        $reply = shell_exec("cd " . Config::$nodejs_folder . "; node delegate.js $nearAccount $nearPrivateKey $recipient $amount 2>&1");
                        $data['text'] = $reply;

                    } else
                        $data['text'] = "Wrong data";


                    Request::sendMessage($data);
                    $this->conversation->stop();
                    break;
                }
        }

        return $result;
    }
}