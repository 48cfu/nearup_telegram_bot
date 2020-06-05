<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Request;
use Settings\Config;

include_once __DIR__ . '/../bot.php';

class SeatPriceCommand extends MyCommand
{
    protected $name = 'seatPrice';
    protected $description = 'Get Seat Price';
    protected $usage = '/getSeatPrice';
    protected $version = '1.0.0';

    public function execute()
    {
        parent::execute();
        if (!$this->ValidateAccess())
            return false;

        $price = shell_exec("cd " . Config::$nodejs_folder . "; node getSeatPrice.js 2>&1");
        $price = self::CleanNodejsOutput($price);

        $price = trim($price);

        $data = [
            'chat_id' => $this->chat_id,
            'text' => "{$this->strings["title"]}: `$price NEAR`",
            'parse_mode' => 'markdown'
        ];

        return Request::sendMessage($data);
    }
}