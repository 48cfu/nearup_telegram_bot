<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultArticle;
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;
use Longman\TelegramBot\Request;
use Near\NearData;

include_once __DIR__ . '/../near.php';

class InlinequeryCommand extends SystemCommand
{
    protected $name = 'inlinequery';
    protected $description = 'Reply to inline query';
    protected $version = '1.0.0';

    public function execute()
    {
        $inline_query = $this->getInlineQuery();
        $query        = $inline_query->getQuery();

        $data    = ['inline_query_id' => $inline_query->getId()];
        $results = [];

        if ($query !== '') {
            $accountData = NearData::GetAccountBalance($query);
            if(isset($accountData["result"])) {
                $output = [
                    "Balance: " . NearData::RoundNearBalance($accountData["result"]["amount"]),
                    "Locked: " . NearData::RoundNearBalance($accountData["result"]["locked"]),
                    "Storage Usage: " . NearData::RoundNearBalance($accountData["result"]["storage_usage"])
                ];

                $replies = [
                    [
                        'id' => '001',
                        'title' =>  "Account " . $query,
                        'description' =>  join(chr(10), $output),
                        'input_message_content' => new InputTextMessageContent(['message_text' => '/ViewAccount@nearup_bot ' . $query]),
                    ]
                ];

                foreach ($replies as $reply) {
                    $results[] = new InlineQueryResultArticle($reply);
                }
            }
        }

        return $this->getInlineQuery()->answer($results, $data);
    }
}