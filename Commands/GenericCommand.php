<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Bot\Common;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Near\NearView;

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

        if (!Common::ValidateAccess($chat_id, $message->getMessageId(), $user_id))
            return false;

        if (stripos($command_lower, 'viewaccount_') === 0) {
            $account = substr($text, strpos($text, "_") + 1);
            $account = str_replace("_", ".", $account);

            if($account) {
                $reply = NearView:: GetAccountDetails($account);
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply,
                ];
                return Request::sendMessage($data);
            }
        }
        else  if (stripos($command_lower, 'viewaccesskey_') === 0) {
            $account = substr($text, strpos($text, "_") + 1);
            $account = str_replace("_", ".", $account);

            if($account) {
                $reply = NearView:: GetAccountAccessKeysDetails($account);
                $data = [
                    'chat_id' => $chat_id,
                    'text' => $reply,
                ];
                return Request::sendMessage($data);
            }
        }

        $data = [
            'chat_id' => $chat_id,
            'text'    => 'Command /' . $command . ' not found. Please /start again.',
        ];

        return Request::sendMessage($data);
    }
}