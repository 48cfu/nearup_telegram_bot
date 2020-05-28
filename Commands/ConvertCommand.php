<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Near\NearData;
use Longman\TelegramBot\Request;

class ConvertCommand extends MyCommand
{
    protected $name = 'convert';
    protected $description = 'Convert Near balances';
    protected $usage = '/convert';
    protected $version = '1.0.0';

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

        switch ($state) {
            case 0:
                if ($this->text === '' || !in_array($this->text, ['NEAR -> yoctoNEAR', 'yoctoNEAR -> NEAR'], true)) {

                    $data['reply_markup'] = (new Keyboard(['NEAR -> yoctoNEAR', 'yoctoNEAR -> NEAR']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = $this->strings["pleaseChooseConvertDirection"];
                    Request::sendMessage($data);

                    break;
                }

                $notes['direction'] = $this->text;
                $this->text = '';

            case 1:
                if ($this->text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    $data['text'] = $this->strings["pleaseEnterAmountToConvert"];
                    Request::sendMessage($data);
                    break;
                }
                $notes['amount'] = $this->text;
                $this->text = '';

            case 2:
                if ($this->text === '') {
                    $amount = $notes['amount'];
                    $reply = "";

                    if ($notes['direction'] === "NEAR -> yoctoNEAR") {
                        $reply = NearData::ConvertNearToYoctoNear($amount);
                    } elseif ($notes['direction'] === "yoctoNEAR -> NEAR") {
                        $reply = NearData::RoundNearBalance($amount);
                    }

                    if ($reply) {
                        $data['text'] = "{$this->strings["result"]}:\n`$reply`";
                        $data['parse_mode'] = 'markdown';
                    }
                    else
                        $data['text'] =  $this->strings["wrongData"];

                    Request::sendMessage($data);
                    $this->conversation->stop();
                    break;
                }
        }

        return $result;
    }
}