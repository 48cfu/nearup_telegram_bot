<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Near\NearData;
use Longman\TelegramBot\Request;

class AddNodeCommand extends MyCommand
{
    protected $name = 'addNode';
    protected $description = 'Add Node Account';
    protected $usage = '/addNode';
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
        $pdo = NearData::InitializeDB();

        switch ($state) {
            case 0:
                if ($this->text === '') {

                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = $this->strings["addNodeQuestion"];
                    Request::sendMessage($data);

                    break;
                }

                $notes['nodeAccount'] = trim($this->text);
                $this->text = '';

            case 1:
                if ($this->text === '') {
                    $notes['state'] = 1;
                    $nodeAccount = strtolower($notes['nodeAccount']);
                    NearData::setNodeAccount($pdo, $this->user_id, $nodeAccount);

                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    $data['parse_mode'] = 'markdown';
                    $data['text'] = $this->GenerateOutput($this->strings["nodeAccountAdded"], [$nodeAccount]);
                    Request::sendMessage($data);
                    $this->conversation->stop();
                    break;
                }
                $this->text = '';
        }

        return $result;
    }
}