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
        if ($message->getType() == 'WITH_GROUP' && $message->getCommand() && $message->isSelfCommand() && ($message->getCommand() == '/setup' || $message->getCommand() == '/start')) {
            $result = self::getAnswerSetup($message->getSourceId(), $message->getFullname());
        }

        // chat voi bot
        if ($message->getType() == 'WITH_BOT') {
            $result = self::getAnswerDefault($message->getFullname());
        }

        return $result;
    }

    private static function getAnswerSetup($groupId, $fullname) {
        $urlFrontend = env("FRONTEND_URL");
        $linkSetup = $urlFrontend ."/group/". $groupId ."/setup";
        $text = "Xin chào, <b>" . $fullname . "</b>";

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

    private static function getAnswerDefault($fullname) {
        $urlFrontend = env("FRONTEND_URL");
        $botUsername = env("BOT_USERNAME", 'notice2bot');
        $urlAddbot = 'https://t.me/' . $botUsername . '?startgroup=domon';
        $text = "Xin chào, <b>" . $fullname . "</b>";

        return [
            'text'  =>  $text,
            'reply_markup'  =>  [
                'inline_keyboard'  =>  [
                    [

                        [
                            'text'  =>  'ℹ Thêm bot vào group',
                            'url' =>  $urlAddbot
                        ],
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
