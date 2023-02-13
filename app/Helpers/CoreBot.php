<?php

namespace App\Helpers;

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;

class CoreBot
{
    public static function getAnswer($message)
    {
        $result = null;

        // chat command voi group
        if ($message->getType() == 'WITH_GROUP' && $message->getCommand() && $message->isSelfCommand() && $message->getCommand() == '/setup') {
            $result = self::getAnswerSetup($message->getSourceId());
        }

        // chat voi bot
        if ($message->getType() == 'WITH_BOT') {
            $result = self::getAnswerDefault();
        }

        return $result;
    }

    private static function getAnswerSetup($groupId) {
        $urlFrontend = env("FRONTEND_URL");
        $linkSetup = $urlFrontend ."/group/". $groupId ."/setup";
        $text = "Truy cập vào cài đặt";

        return [
            'text'  =>  $text,
            'reply_markup'  =>  [
                'inline_keyboard'  =>  [
                    [
                        [
                            'text'  =>  'ℹ Cấu hình',
                            'url' =>  $linkSetup
                        ]
                    ]
                ]
            ]
        ];
    }

    private static function getAnswerDefault() {
        $urlFrontend = env("FRONTEND_URL");
        $text = "Truy cập website";

        return [
            'text'  =>  $text,
            'reply_markup'  =>  [
                'inline_keyboard'  =>  [
                    [
                        [
                            'text'  =>  'ℹ Website',
                            'url' =>  $urlFrontend
                        ]
                    ]
                ]
            ]
        ];
    }

    private static function replaceByArray($arr, $text) {
        foreach ($arr as $key => $val) {
            $text = str_replace("[".$key."]", $val, $text);
        }
        return $text;
    }
}
