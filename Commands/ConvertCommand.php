<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Near\NearData;

include_once __DIR__ . '/../bot.php';

class ConvertCommand extends UserCommand
{
    protected $name = 'convert';
    protected $description = 'Convert Near balances';
    protected $usage = '/convert';
    protected $version = '1.0.0';

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

        switch ($state) {
            case 0:
                if ($text === '' || !in_array($text, ['NEAR -> yoctoNEAR', 'yoctoNEAR -> NEAR'], true)) {

                    $data['reply_markup'] = (new Keyboard(['NEAR -> yoctoNEAR', 'yoctoNEAR -> NEAR']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = "Please choose convert direction:";
                    Request::sendMessage($data);


                    break;
                }

                $notes['direction'] = $text;
                $text = '';

            case 1:
                if ($text === '') {
                        $notes['state'] = 1;
                        $this->conversation->update();
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                        $data['text'] = "Please enter the amount to convert:";
                        Request::sendMessage($data);
                        break;
                }
                $notes['amount'] = $text;
                $text = '';

            case 2:
                if ($text === '') {
                    $amount =  $notes['amount'];
                    $reply = "";

                    if($notes['direction'] === "NEAR -> yoctoNEAR"){
                       $reply = NearData::ConvertNearToYoctoNear($amount);
                    }
                    elseif($notes['direction'] === "yoctoNEAR -> NEAR") {
                        $reply = NearData::RoundNearBalance($amount);
                    }

                    if($reply)
                        $data['text'] = "Result:".PHP_EOL.$reply;
                    else
                        $data['text'] = "Wrong data";

                    Request::sendMessage($data);
                    $this->conversation->stop();
                    break;
                }
        }

        return $result;
    }
}