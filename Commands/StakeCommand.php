<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Near\NearData;
use Settings\Config;

class StakeCommand extends MyCommand
{
    protected $name = 'stake';
    protected $description = 'Only Stake to the Staking Pool';
    protected $usage = '/stake';
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
                        $notes['nearAccountId'] = $nearLogin;
                        $notes['state'] = 0;
                        $this->conversation->update();

                        $data['text'] = $this->strings["pleaseEnterStakingPoolContract"];
                        $data['parse_mode'] = 'markdown';
                        Request::sendMessage($data);
                    }

                    break;
                }

                $notes['recipient'] = $this->text;
                $this->text = '';

            case 1:
                if ($this->text === '') {

                    if ($notes['recipient']) {
                        $notes['state'] = 1;
                        $this->conversation->update();
                        $recipient = $notes['recipient'];
                        $data['text'] = "{$this->strings["howManyTokensStake"]} $recipient?";
                        Request::sendMessage($data);

                        break;
                    }
                }

                $notes['amount'] = $this->text;
                $this->text = '';

            case 2:
                if ($this->text === '') {

                    $nearPrivateKey = NearData::GetPrivateKey($pdo, $this->user_id);
                    $nearAccount = $notes['nearAccountId'];
                    $amount = $notes['amount'];
                    $recipient = $notes['recipient'];
                    if (intval($amount) > 0 && $nearPrivateKey && $nearAccount) {
                        $reply = shell_exec("cd " . Config::$nodejs_folder . "; node stake.js $nearAccount $nearPrivateKey $recipient $amount 2>&1");
                        $data['text'] =self::CleanNodejsOutput($reply);

                    } else
                        $data['text'] = $this->strings["wrongData"];


                    Request::sendMessage($data);
                    $this->conversation->stop();
                    break;
                }
        }

        return $result;
    }
}