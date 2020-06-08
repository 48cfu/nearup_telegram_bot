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

class SendTelegramCommand extends UserCommand
{
    protected $name = 'sendTelegram';
    protected $description = 'Send Money to Telegram';
    protected $usage = '/sendTelegram';
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

                        $data['text'] = "Please forward any private message from a recipient's telegram user";
                        Request::sendMessage($data);
                    }

                    break;
                }

                $text = '';

            case 1:
                if ($text === '') {

                    if ($forward_from = $message->getForwardFrom()) {
                        $notes['recipient_id'] = $forward_from->getId();
                        $notes['recipient_username'] = $forward_from->getUsername();
                    }

                    if (!$notes['recipient_id'] || !$notes['recipient_username']) {
                        $data['text'] = "Recipient's telegram account wasn't recognized. Please try again to /sendTelegram";
                        Request::sendMessage($data);
                        $this->conversation->stop();
                        break;
                    } else {
                        $notes['state'] = 1;
                        $this->conversation->update();
                        $recipient = $notes['recipient_username'];
                        $data['text'] = "How much NEAR tokens do you want to send to $recipient?";
                        Request::sendMessage($data);

                        break;
                    }
                }

                $notes['amount'] = $text;
                $text = '';

            case 2:
                if ($text === '') {

                    $recipientId = $notes['recipient_id'];
                    $recipientUsername = $notes['recipient_username'];
                    $recipientNearLogin = NearData::GetUserLogin($pdo, $recipientId);

                    if ($recipientNearLogin) {
                        $data['text'] = "Telegram username $recipientUsername has authorized NEAR account $recipientNearLogin. You can /send some NEAR tokens to this user.";
                        $result = Request::sendMessage($data);
                    } else {
                        $nearAccount = $recipientId . Config::$nearAccountDomain;
                        $reply = shell_exec("cd " . Config::$nodejs_folder . "; node createAccount.js $nearAccount 2>&1");
                        $userDate = json_decode($reply, true);
                        $publicKey = $userDate['public'];
                        $privateKey = $userDate['private'];
                        $nearLogin = $notes['nearAccountId'];
                        $amount = $notes['amount'];

                        if ($publicKey && $privateKey && $nearLogin && $amount) {
                            $status = NearData::SetUserCredentials($pdo, $recipientId, $nearAccount, $publicKey, $privateKey);
                            if ($status) {
                                $nearPrivateKey =  NearData::GetPrivateKey($pdo, $user_id);
                                $reply = shell_exec("cd " . Config::$nodejs_folder . "; node sendMoney.js $nearLogin $nearPrivateKey $nearAccount $amount 2>&1");
                                //$reply = self::CleanNodejsOutput($reply);

                                $errorPosition = strpos($reply, "Error: ");
                                if ($errorPosition > -1) {
                                    $reply = substr($reply, $errorPosition, (strpos($reply, "\n") - $errorPosition));
                                }

                                $data['text'] = $reply;
                                $result = Request::sendMessage($data);
                            }
                        }
                        else{
                            $data['text'] = "Failed";
                            $result = Request::sendMessage($data);
                        }

                    }

                    $this->conversation->stop();
                    break;
                }

                $notes['amount'] = $text;
        }

        return $result;
    }
}