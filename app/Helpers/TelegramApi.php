<?php


namespace App\Helpers;


use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request as TRequest;

class TelegramApi
{
    public const TIME_BAN_FOREVER = -1;
    public const TIME_BAN_1_HOUR = 1;
    public const TIME_BAN_24_HOURS = 24;
    public const TIME_BAN_36_HOURS = 36;

    public static function sendMessage($chatId, $message) {
        try {
            TRequest::sendMessage([
                'chat_id' => $chatId,
                'parse_mode' => 'HTML',
                'text' => $message['text'],
                'reply_markup' => $message['reply_markup']
            ]);
        } catch (TelegramException $e) {
            info('ERROR SEND MESSAGE');
        }
    }

    public static function sendPhoto($chatId, $message) {
        try {
            $response = TRequest::sendPhoto([
                'chat_id' => $chatId,
                'photo' => $message['image'],
                'caption' => $message['title'],
            ]);
            info($response);
        } catch (TelegramException $e) {
            info('ERROR SEND MESSAGE');
        }
    }

    public static function sendVideo($chatId, $message) {
        try {
            $response = TRequest::sendVideo([
                'chat_id' => $chatId,
                'video' => $message['video'],
                'caption' => $message['title'],
            ]);
            info($response);
            return $response->isOk();
        } catch (TelegramException $e) {
            info('ERROR SEND MESSAGE');
        }
        return False;
    }

    public static function sendDocument($chatId, $message) {
        try {
            $response = TRequest::sendDocument([
                'chat_id' => $chatId,
                'document' => $message['file'],
                'caption' => $message['title'],
            ]);
            info($response);
            return $response->isOk();
        } catch (TelegramException $e) {
            info('ERROR SEND MESSAGE');
        }
        return False;
    }

    public static function deleteMessage($message) {
        try {
            $responseDelete = TRequest::deleteMessage([
                'chat_id' => $message->getSourceId(),
                'message_id'    =>  $message->getMessageId()
            ]);
            if ($responseDelete->isOk()) {
                info('Delete success');
                return true;
            } else {
                info('Cannot delete, check permission of bot');
            }
        } catch (TelegramException $e) {
            info('ERROR DELETE MESSAGE');
        }
        return false;
    }

    public static function banUser($chatId, $userId, $timeDiff) {
        $current_time = time();
        switch ($timeDiff) {
            case self::TIME_BAN_1_HOUR:
                $time = $current_time + 3600;
                break;
            case self::TIME_BAN_24_HOURS:
                $time = $current_time + 3600 * 24;
                break;
            case self::TIME_BAN_36_HOURS:
                $time = $current_time + 3600 * 36;
                break;
            case self::TIME_BAN_FOREVER:
                $time = 0;
                break;
            default:
                $time = $current_time + 3600 * $timeDiff;
        }
        try {
            $responseBan = TRequest::restrictChatMember([
                'chat_id' => $chatId,
                'user_id'    =>  $userId,
                'until_date' => $time
            ]);
            if ($responseBan->isOk()) {
                info('Ban success');
                return true;
            } else {
                info('Cannot ban, check permission of bot');
            }
        } catch (TelegramException $e) {
            info('ERROR BAN USER');
        }
        return false;
    }

    public static function kickUser($chatId, $userId) {

        try {
            $responseBan = TRequest::kickChatMember([
                'chat_id' => $chatId,
                'user_id'    =>  $userId
            ]);
            if ($responseBan->isOk()) {
                info('Kick success');
                return true;
            } else {
                info('Cannot kick, check permission of bot');
            }
        } catch (TelegramException $e) {
            info('ERROR BAN USER');
        }
        return false;
    }

    public static function checkAdmin($groupId, $userId) {
        try {
            $chatUser = TRequest::getChatMember([
                'chat_id' => $groupId,
                'user_id'   => $userId
            ])->getResult();
            if ($chatUser) {
                $status = $chatUser->getStatus();
                if ($status == 'administrator' || $status == 'creator') {
                    info('check admin true');
                    return true;
                }
            } else {
                info('check admin false');
                return false;
            }
        } catch (TelegramException $e) {
            info('ERROR GET ADMIN');
        }
        return false;
    }

    public static function getListAdmin($groupId) {
        $strAdmin = '';
        try {
            $admins = TRequest::getChatAdministrators([
                'chat_id' => $groupId
            ])->getResult();
            info($admins);
            if ($admins) {
                foreach($admins as $admin) {
                    $strAdmin .= $admin->user['id'] . ';';
                }
            }
        } catch (TelegramException $e) {
            info('ERROR GET ADMIN');
        }
        return $strAdmin;
    }
}
