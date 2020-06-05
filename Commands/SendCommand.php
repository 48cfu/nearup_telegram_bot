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

class SendCommand extends UserCommand
{
    protected $name = 'send';
    protected $description = 'Send Money within NEAR';
    protected $usage = '/send';
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

        if(!Common::ValidateAccess($chat_id, $message->getMessageId(), $user_id))
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
                $pair = null;
                if ($text === '') {
                    $nearLogin = NearData::GetUserLogin($pdo, $user_id);
                    if(!$nearLogin){
                        $data['text'] = "You didn't authorize current telegram account with the NEAR account. Please click /login to proceed";
                        Request::sendMessage($data);
                        $this->conversation->stop();
                    }
                    else{
                        $notes['accountId'] =  $nearLogin;
                        $notes['state'] = 0;
                        $this->conversation->update();

                        $data['text'] = 'Please enter recipient account';
                        Request::sendMessage($data);
                    }

                    break;
                }

                $notes['recipient'] = $text;
                $text = '';

            // no break
            case 1:
                if ($text === '') {

                    $recipient = $notes['recipient'];

                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['text'] = "How much NEAR tokens do you want to send to $recipient?";
                    $result = Request::sendMessage($data);

                    break;
                }

                $notes['amount'] = $text;
                $text = '';
            case 2:
                if ($text === '') {
                    $nearLogin = $notes['accountId'];
                    $recipient = $notes['recipient'];
                    $amount = $notes['amount'];
                    if($amount && $nearLogin && $recipient) {
                        $nearPrivateKey = NearData::GetPrivateKey($pdo, $user_id);

                        $reply = shell_exec("cd " . Config::$nodejs_folder . "; node sendMoney.js $nearLogin $nearPrivateKey $recipient $amount 2>&1");
                        //$reply = self::CleanNodejsOutput($reply);

                        $errorPosition =strpos( $reply, "Error: ");
                        if($errorPosition> -1){
                            $reply =  substr($reply, $errorPosition,(strpos($reply, "\n") - $errorPosition));
                        }
                        $data['text'] = $reply;
                        $result = Request::sendMessage($data);

                    }
                    else{
                        $data['text'] = "Wrong data, please try to /send again";
                        $result = Request::sendMessage($data);
                    }

                    $this->conversation->stop();
                    break;
                }
        }

        return $result;
    }
}