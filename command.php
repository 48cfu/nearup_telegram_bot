<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Settings\Config;
use Longman\TelegramBot\Request;

class MyCommand extends UserCommand
{
    protected $name;

    public $message;
    public $user;

    protected $chat_id;
    protected $user_id;
    protected $message_id;

    public function GetText(){
        $json = null;

        switch($this->user->getLanguageCode()){
            case "ru":
                $file = "ru";
                break;
            default:
                $file = "en";
        }

        $json = json_decode(file_get_contents("locales/$file.json"), true);

        if(isset($json, $this->name))
            return $json[$this->name];
        else
            return [];
    }

    public function execute()
    {
        $this->message = $this->getMessage();
        $this->user =  $this->message->getFrom();

        $this->chat_id = $this->message->getChat()->getId();
        $this->user_id = $this->user->getId();
        $this->message_id =  $this->message->getMessageId();
    }

    public function GenerateOutput($messagesArray){
        return join(chr(10), $messagesArray);
    }

    public function ValidateAccess(){
        if (in_array( $this->chat_id, Config::$restrictedChatIds) && !in_array( $this->user_id,  Config::$adminIds)) {
            Request::deleteMessage([
                'chat_id' => $this->chat_id,
                'message_id' => $this->message_id,
            ]);
            return false;
        }
        return true;
    }
}