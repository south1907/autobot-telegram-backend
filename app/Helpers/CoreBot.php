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

        if ($message->getType() == 'WITH_GROUP' && $message->getCommand() && $message->isSelfCommand() &&
            $message->getCommand() == '/mute' && $message->getReplyToUserId()) {
            $text = $message->getText();
            $splitText = explode(' ', $text);
            $flagMute = false;
            $timeNumber = 0;
            if (count($splitText) == 1) {
                $timeNumber = 0;
                $flagMute = true;
            }
            if (count($splitText) == 2) {
                $shortTime = $splitText[1];
                if ($shortTime == 'forever') {
                    $timeNumber = 0;
                    $flagMute = true;
                } else {
                    if (substr($shortTime, -1) == 'h') {
                        $timeNumber = substr($shortTime, 0, strlen($shortTime) - 1);
                        $flagMute = true;
                    }
                }
            }
            if ($flagMute && is_numeric($timeNumber)) {
                try {
                    $tokenBot = env('BOT_TOKEN');
                    new Telegram($tokenBot);
                } catch (TelegramException $e) {
                    info('ERROR CLIENT TELEGRAM');
                    return null;
                }
                $checkAdmin = TelegramApi::checkAdmin($message->getSourceId(), $message->getUserId());
                if (!$checkAdmin) {
                    return null;
                }
                info ('ban by reply');
                $checkBan = TelegramApi::banUser($message->getSourceId(), $message->getReplyToUserId(), $timeNumber);
                if ($checkBan) {
                    $timeString = $timeNumber . ' hour(s)';
                    if ($timeNumber == 0) {
                        $timeString = 'forever';
                    }
                    $result = self::getAnswerMute($message->getReplyToUserFullname(), $timeString);
                } else {
                    $result = self::getAnswerMuteFail();
                }
            }
        }

        // chat voi bot
        if ($message->getType() == 'WITH_BOT') {
            $result = self::getAnswerDefault();
        }

        return $result;
    }

    public static function getHelloMessage($message) {
        $fullname = $message->getFirstName() . ' ' . $message->getLastName();
        $fullname = trim($fullname);
        $fullname = self::checkFullname($fullname);

        $text = self::getContentNotice('WELCOME_JOIN');
        $text = self::replaceByArray([
            'fullname'  =>  $fullname
        ], $text);
        return [
            'text'  =>  $text,
            'reply_markup'  =>  null
        ];
    }

    public static function getWarningMessage($message, $type) {
        $fullname = $message->getFirstName() . ' ' . $message->getLastName();
        $fullname = trim($fullname);
        $fullname = self::checkFullname($fullname);
        $keyNotice = '';
        switch ($type) {
            case MessageHelper::CHECK_FALSE_COMMENT:
                $keyNotice = 'NOTICE_COMMENT';
                break;
            case MessageHelper::CHECK_FALSE_FOWRARD:
                $keyNotice = 'NOTICE_FORWARD';
                break;
            case MessageHelper::CHECK_FALSE_LINK:
                $keyNotice = 'NOTICE_LINK';
                break;
        }
        if ($keyNotice != '') {
            $text = self::getContentNotice($keyNotice);
            $text = self::replaceByArray([
                'fullname'  =>  $fullname
            ], $text);
        } else {
            $text = self::getWarningTextMedia($fullname, $message->getTypeMessage());
        }
        return [
            'text'  =>  $text,
            'reply_markup'  =>  null
        ];
    }

    private static function getWarningTextMedia($fullname, $typeMedia) {
        $fullname = self::checkFullname($fullname);
        $namePackSticker = env("PACK_STICKER", "StickerZUPI");
        $textType = 'media';
        // IMAGE, AUDIO, GIF, VIDEO, STICKER, FILE
        switch ($typeMedia) {
            case 'IMAGE':
                $textType = 'ảnh';
                break;
            case 'AUDIO':
                $textType = 'audio';
                break;
            case 'GIF':
                $textType = 'ảnh động';
                break;
            case 'VIDEO':
                $textType = 'video';
                break;
            case 'STICKER':
                $textType = 'sticker';
                break;
            case 'FILE':
                $textType = 'file';
                break;
        }
        $keyNotice = 'NOTICE_MEDIA';
        if ($typeMedia == 'STICKER') {
            $keyNotice = 'NOTICE_STICKER';
        }
        $text = self::getContentNotice($keyNotice);
        $text = self::replaceByArray([
            'fullname'  =>  $fullname,
            'type_media'  =>  $textType,
            'sticker_package'  =>  $namePackSticker
        ], $text);
        return $text;
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

    private static function getAnswerMute($fullname, $time) {
        $fullname = self::checkFullname($fullname);
        $text = self::getContentNotice('NOTICE_MUTE');
        $text = self::replaceByArray([
            'fullname'  =>  $fullname,
            'time'  =>  $time
        ], $text);

        return [
            'text'  =>  $text,
            'reply_markup'  =>  null
        ];
    }

    private static function getAnswerMuteFail() {
        $text = "block error, check group is supergroup or permission of bot";

        return [
            'text'  =>  $text,
            'reply_markup'  =>  null
        ];
    }

    private static function getAnswerDefault() {
        return [
            'text'  =>  self::getContentNotice('DEFAULT_ANSWER'),
            'reply_markup'  =>  null
        ];
    }

    private static function checkContainWord($checkText, $arrWord) {
        $checkText = strtolower($checkText);
        foreach ($arrWord as $word) {
            $word = trim($word);
            if (empty($word)) {
                continue;
            }
            $word = strtolower($word);
            if (str_contains($checkText, $word)) {
                return true;
            }
        }
        return false;
    }

    private static function replaceByArray($arr, $text) {
        foreach ($arr as $key => $val) {
            $text = str_replace("[".$key."]", $val, $text);
        }
        return $text;
    }
}
