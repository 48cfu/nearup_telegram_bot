<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Settings\Config;
use Longman\TelegramBot\Request;

class MyCommand extends UserCommand
{
    protected $name;

    protected $message;
    protected $user;
    protected $text;

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

        $screenText = [];

        if(isset($json, $this->name))
            $screenText = $json[$this->name];

        return array_merge($screenText, $json["general"]);

    }

    public function execute()
    {
        $this->message = $this->getMessage();
        $this->user =  $this->message->getFrom();

        $this->text = $this->GetText();

        $this->chat_id = $this->message->getChat()->getId();
        $this->user_id = $this->user->getId();
        $this->message_id =  $this->message->getMessageId();
    }

    public function GenerateOutput($message, $valuesArray = []){
        if(is_array($message))
            $message = join(chr(10), $message);

        for($i=0; $i<count($valuesArray); $i++)
            $message = str_replace("{%$i%}", $valuesArray[$i], $message);

        return $message;
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