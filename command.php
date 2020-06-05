<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Near\NearData;
use Settings\Config;
use Longman\TelegramBot\Request;

class MyCommand extends UserCommand
{
    protected $name;

    protected $message;
    protected $user;
    protected $text;
    protected $chat;
    protected $chat_id;
    protected $user_id;
    protected $message_id;

    protected $strings;

    public static function GetText($user, $commandName)
    {
        $json = null;

        switch ($user->getLanguageCode()) {
            case "ru":
                $file = "ru";
                break;
            default:
                $file = "en";
        }

        $json = json_decode(file_get_contents("locales/$file.json"), true);

        $screenText = [];

        if (isset($json[$commandName]))
            $screenText = $json[$commandName];

        if ($screenText && is_array($screenText))
            return array_merge($screenText, $json["general"]);
        else
            return $json["general"];

    }

    public function execute()
    {
        $this->message = $this->getMessage();
        $this->user = $this->message->getFrom();
        $this->chat = $this->message->getChat();
        $this->text = trim($this->message->getText(true));
        $this->chat_id = $this->message->getChat()->getId();
        $this->user_id = $this->user->getId();
        $this->message_id = $this->message->getMessageId();

        $this->strings = $this->GetText($this->user, $this->name);
    }

    public static function CleanNodejsOutput($string)
    {
        foreach (Config::$stringToCleanFromNodeJs as $fix) {
            $string = str_replace($fix, "", $string);
        }
        return $string;
    }


    public static function GenerateOutput($message, $valuesArray = [])
    {
        if (is_array($message))
            $message = join(chr(10), $message);

        for ($i = 0; $i < count($valuesArray); $i++)
            $message = str_replace("{%$i%}", $valuesArray[$i], $message);

        return $message;
    }

    public function ValidateAccess()
    {
        return $this->ValidateAccessWithParameters($this->chat_id, $this->message_id, $this->user_id);
    }

    public static function ValidateAccessWithParameters($chat_id, $message_id, $user_id)
    {
        if (in_array($chat_id, Config::$restrictedChatIds) && !in_array($user_id, Config::$adminIds)) {
            Request::deleteMessage([
                'chat_id' => $chat_id,
                'message_id' => $message_id,
            ]);
            return false;
        }
        return true;


    }
}