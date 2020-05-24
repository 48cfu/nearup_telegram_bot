<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Near\NearView;

include_once __DIR__ . '/../bot.php';
include_once __DIR__ . '/../near.php';

class ViewAccountCommand extends UserCommand
{
    protected $name = 'viewAccount';
    protected $description = 'View Account';
    protected $usage = '/viewAccount';
    protected $version = '1.0.0';

    protected $conversation;

    public function execute()
    {
        $message = $this->getMessage();
        $chat = $message->getChat();
        $text = trim($message->getText(true));
        $text_full = trim($message->getText(false));
        $chat_id = $chat->getId();
        $user = $message->getFrom();
        $user_id = $user->getId();

        if (!Common::ValidateAccess($chat_id, $message->getMessageId(), $user_id))
            return false;

        $data = [
            'chat_id' => $chat_id
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
                if ($text === '') {
                    $paramPosition = strpos($text_full, " ");
                    if ($paramPosition > -1) {
                        $account = substr($text_full, $paramPosition + 1);
                        $data['text'] = NearView:: GetAccountDetails($account);
                        Request::sendMessage($data);

                        $this->conversation->stop();
                        break;
                    } else {
                        $notes['state'] = 0;
                        $this->conversation->update();
                        $data['text'] = "Please enter NEAR account name";
                        Request::sendMessage($data);
                    }
                }

                $notes['account'] = $text;
                $text = '';

            case 1:
                if ($text === '' && $notes['account']) {
                    $data['text'] = NearView:: GetAccountDetails($notes['account']);
                    $result = Request::sendMessage($data);
                    $this->conversation->stop();
                }
        }
        return $result;
    }


}