<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Request;

class LogoutCommand extends MyCommand
{
    protected $name = 'logout';
    protected $description = 'Logout';
    protected $usage = '/logout';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $pdo = NearData::InitializeDB();
        $nearLogin = NearData::GetUserLogin($pdo, $this->user_id);

        $data = [
            'chat_id' => $this->chat_id,
        ];

        if(!$nearLogin)
            $data['text'] = $this->strings["loginWasntFound"];
        else{
            NearData:: saveUserDetails($pdo, $this->user_id, "", "", "");
            $data['text'] = $this->GenerateOutput($this->strings["nearAccountSuccessfullyRemoved"], [$nearLogin]);
        }

        return Request::sendMessage($data);
    }
}