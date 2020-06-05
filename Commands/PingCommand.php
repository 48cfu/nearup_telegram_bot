<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Near\NearData;
use Settings\Config;

include_once __DIR__ . '/../bot.php';

class PingCommand extends MyCommand
{
    protected $name = 'ping';
    protected $description = 'Ping Contract';
    protected $usage = '/ping';
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
                        $notes['near_account_id'] = $nearLogin;
                        $data['text'] = self::GenerateOutput($this->strings["enterContractName"], [$nearLogin]);
                        $data['parse_mode'] = "markdown";
                        Request::sendMessage($data);

                        $notes['state'] = 0;
                        $this->conversation->update();
                    }

                    break;
                }

                $notes['contract_name'] = $this->text;
                $this->text = '';

            case 1:
                if ($this->text === '') {

                    if ($notes['contract_name']) {
                        $nearPrivateKey = NearData::GetPrivateKey($pdo, $this->user_id);
                        $nearAccount = $notes['near_account_id'];
                        $contractName = $notes['contract_name'];
                        if ($nearPrivateKey && $nearAccount && $contractName) {
                            $reply = shell_exec("cd " . Config::$nodejs_folder . "; node ping.js $nearAccount $nearPrivateKey $contractName 2>&1");
                            $data['text'] = self::CleanNodejsOutput($reply);

                        } else
                            $data['text'] = $this->strings["wrongData"];
                    } else
                        $data['text'] = self::GenerateOutput($this->strings["exitTryAgain"], ["ping"]);

                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                    Request::sendMessage($data);
                    $this->conversation->stop();
                    break;
                }
        }

        return $result;
    }
}