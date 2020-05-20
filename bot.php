<?

namespace Bot;

use Longman\TelegramBot\Request;
use Settings\Config;

Class Common{
    public static function ValidateAccess($chatId, $messageId, $userId){
        if (in_array($chatId, Config::$restrictedChatIds) && !in_array($userId,  Config::$adminIds)) {
            Request::deleteMessage([
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
            return false;
        }
        return true;
    }
}