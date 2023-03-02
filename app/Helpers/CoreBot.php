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

        $results = [];
        $setupAnswer = [
            'type'  =>  'text',
            'data'  =>  [
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
            ]
        ];
        $results[] = $setupAnswer;
        return $results;
    }

    private static function getAnswerDefault($fullname) {
        $urlFrontend = env("FRONTEND_URL");
        $botUsername = env("BOT_USERNAME", 'notice2bot');
        $urlAddbot = 'https://t.me/' . $botUsername . '?startgroup=domon';
        $text = "Xin chào, <b>" . $fullname . "</b>";

        $results = [];

        // cau chao
        $results[] = [
            'type'  =>  'text',
            'data'  =>  [
                'text'  =>  $text,
                'reply_markup'  =>  null
            ]
        ];

        // hinh anh
        $results[] = [
            'type'  =>  'image',
            'data'  =>  [
                'image'  =>  'https://bot.sachkinhhay.com/static/media/demo.png',
                'title'  =>  null
            ]
        ];

        // huong dan
        $textHuongdan = "Đây là bot ứng dụng chia sẻ Chánh Pháp của Phật Như Lai đến với mọi người, được sử dụng ở trong các Group. \n\n<b>Hướng dẫn sử dụng:</b> \n Bước 1: Thêm bot vào Group \n Bước 2: Set quyền Admin cho bot để bot có thể đăng bài \n Bước 3: Vào mục Cài Đặt và đăng nhập trên trang web và cấu hình nội dung được đăng vào trong Group";
        $results[] = [
            'type'  =>  'text',
            'data'  =>  [
                'text'  =>  $textHuongdan,
                'reply_markup'  =>  null
            ]
        ];

        // nut tra loi
        $results[] = [
            'type'  =>  'text',
            'data'  =>  [
                'text'  =>  null,
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
            ]
        ];

        return $results;
    }

    private static function replaceByArray($arr, $text) {
        foreach ($arr as $key => $val) {
            $text = str_replace("[".$key."]", $val, $text);
        }
        return $text;
    }
}
