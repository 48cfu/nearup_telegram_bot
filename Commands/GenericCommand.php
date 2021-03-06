<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Commands\UserCommands\MyCommand;
use Longman\TelegramBot\Request;
use Near\NearView;
use Settings\Config;

include_once __DIR__ . '/../bot.php';

class GenericCommand extends SystemCommand
{
    protected $name = 'generic';
    protected $description = 'Handles generic commands or is executed by default when a command is not found';
    protected $version = '1.1.0';

    public function execute()
    {
        $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();
        $command = $message->getCommand();
        $command_lower = strtolower($command);
        $text = trim($message->getText(false));
        $user = $message->getFrom();
        $user_id = $user->getId();
        $strings = MyCommand::GetText($user, null);
        $message_id = $message->getMessageId();

        $allowDelete = substr_count($text, "/") === 0 && strpos($text, '0') === false;

        if (!MyCommand::ValidateAccessWithParameters($chat_id, $message_id, $user_id, $allowDelete))
            return false;

        if ($command_lower === "всёплохо") {
            $data = [
                'chat_id' => $chat_id,
                'text' => "Попробуйте переключиться на betanet командой\n`export NODE_ENV=betanet`",
                'parse_mode' => 'markdown',
            ];
            return Request::sendMessage($data);
        }  if ($command_lower === "нодаспит") {
        $data = [
            'chat_id' => $chat_id,
            'text' => "Если нода не производит блоки после запуска делегирования через стейкинг-пул, убедитесь, что прописали имя аккаунта от контракта стейкинг-пула в поле `account_id` в файле `~/.near/betanet/validator_key.json` и перезапустили ноду",
            'parse_mode' => 'markdown',
        ];
        return Request::sendMessage($data);
    }else if (stripos($command_lower, 'viewaccount_') === 0) {
            $account = substr($text, strpos($text, "_") + 1);
            $account = str_replace("_", ".", $account);

            if ($account) {
                $reply = NearView:: GetAccountDetails($account, $strings);
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply,
                    'parse_mode' => 'markdown'
                ];
                return Request::sendMessage($data);
            }
        } else if (stripos($command_lower, 'viewaccesskey_') === 0) {
            $account = substr($text, strpos($text, "_") + 1);
            $account = str_replace("_", ".", $account);

            if ($account) {
                $reply = NearView:: GetAccountAccessKeysDetails($account, $strings);
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply,
                    'parse_mode' => 'markdown'
                ];
                return Request::sendMessage($data);
            }
        }

        $data = [
            'chat_id' => $chat_id,
            'text' => MyCommand::GenerateOutput($strings["commandNotFound"], [$command]),
        ];

        return Request::sendMessage($data);
    }
}