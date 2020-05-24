<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Near\NearView;

class ViewAccessKeyCommand extends MyCommand
{
    protected $name = 'viewAccessKey';
    protected $description = 'View Access Key';
    protected $usage = '/viewAccessKey';
    protected $version = '1.0.0';

    protected $conversation;

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $text_full = trim($this->message->getText(false));

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

        switch ($state) {
            case 0:
                if ($this->text === '') {
                    $paramPosition = strpos($text_full, " ");
                    if ($paramPosition > -1) {
                        $account = substr($text_full, $paramPosition + 1);
                        $data['text'] = NearView:: GetAccountAccessKeysDetails($account, $this->strings);
                        $data['parse_mode'] = 'markdown';
                        Request::sendMessage($data);

                        $this->conversation->stop();
                        break;
                    } else {
                        $notes['state'] = 0;
                        $this->conversation->update();
                        $data['text'] = $this->strings["pleaseEnterNearAccountName"];
                        Request::sendMessage($data);
                    }
                }
                $notes['account'] = $this->text;
                $this->text = '';

            case 1:
                if ($this->text === '' && $notes['account']) {
                    $data['text'] = NearView:: GetAccountAccessKeysDetails($notes['account'], $this->strings);
                    $data['parse_mode'] = 'markdown';
                    $result = Request::sendMessage($data);
                    $this->conversation->stop();
                }
        }
        return $result;
    }
}