<?php

namespace Longman\TelegramBot\Commands\UserCommands;

include_once __DIR__ . '/../bot.php';

use Bot\Common;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Near\NearData;

class StartCommand extends UserCommand
{
    protected $name = 'start';
    protected $description = 'Welcome Screen';
    protected $usage = '/start';
    protected $version = '1.0.0';

    public function execute()
    {
        $message = $this->getMessage();
        $user = $message->getFrom();
        $user_id = $user->getId();
        $chat_id = $message->getChat()->getId();

        if(!Common::ValidateAccess($chat_id, $message->getMessageId(), $user_id))
            return false;

        $pdo = NearData::InitializeDB();
        $nearLogin = NearData::GetUserLogin($pdo, $user_id);

        $menu[] = "Near Shell Betanet Bot";
        if ($nearLogin) {
            $menu[] = "Your current Near account: $nearLogin";
            $menu[] = "/checkBalance - Check Balance";
            $menu[] = "/send - Send tokens to NEAR account";
            $menu[] = "/sendTelegram - Send tokens to Telegram account";
        } else
            $menu[] = "/login - Authorize this bot in your NEAR account";

        $menu = array_merge($menu, [
            "/ViewAccount username - Account data",
            "/SeatPrice - Minimal stake for validator",
            "/CurrentValidators - Current Validators",
            "/NextValidators - Next Validators",
            "/CurrentProposals - Current Proposals",
            // "/CurrentFishermen - CurrentFishermen",
            // "/NextFishermen - Next Fishermen",
            "/GetKickouts - Previous epoch kickouts",
            "/about - About bot"
        ]);

        $data = [
            'chat_id' => $chat_id,
            'text' => join(chr(10), $menu),
        ];

        return Request::sendMessage($data);
    }
}