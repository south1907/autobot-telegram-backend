<?php


namespace App\Http\Controllers;

use App\Helpers\CoreBot;
use App\Helpers\MessageHelper;
use App\Helpers\TelegramApi;
use App\Models\Group;
use App\Models\Setting;
use App\Models\Telegram\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;

class BotController extends Controller
{
    public function webhook(Request $request, $tokenBot)
    {
        $input = $request->all();
        info("Receive message");
        info(print_r($input, true));

        if ($tokenBot == env('BOT_TOKEN')) {
            $message = new Message($input);

            if ($message->checkNull() || $message->checkNullTypeMessage()) {
                info("UNKNOWN TYPE MESSAGE");
                return false;
            }

            $groupId = $message->getSourceId();
            if ($message->getType() == 'BOT_JOIN') {
                // create group
                $checkGroup = Group::where('id_telegram', $groupId)->first();
                if ($checkGroup) {
                    info("group " . $groupId . " had a bot, not create group data");
                } else {
                    info("group " . $groupId . " bot join, create group data");
                    $group = new Group();
                    $group->name = $message->getSourceTitle();
                    $group->id_telegram = $message->getSourceId();
                    $group->user_id_telegram = $message->getUserId();
                    $group->active = 0;
                    $group->time_delay = 86400;
                    $setting = Setting::where('type', 'SYSTEM')->first();
                    if ($setting && $setting->default_time_delay) {
                        $group->time_delay = $setting->default_time_delay;
                    }

                    $group->time_next_run = Carbon::now()->addSeconds($group->time_delay)->toDateTimeString();

                    $group->save();
                }
            }

            if ($message->getType() == 'BOT_LEFT') {
                $findGroup = Group::where('id_telegram', $groupId)->first();
                if ($findGroup) {
                    info("group " . $groupId . " remove a bot, delete group data");
                    $findGroup->delete();
                }
            }

            $answer = null;
            if ($message->getType() == 'WITH_BOT') {
                // only get answer
                $answer = CoreBot::getAnswer($message);
            }

            if ($message->getType() == 'WITH_GROUP') {
                if ($message->getCommand() && $message->isSelfCommand()) {
                    // command
                    $answer = CoreBot::getAnswer($message);
                }
            }
            if ($answer) {
                try {
                    new Telegram($tokenBot);
                } catch (TelegramException $e) {
                    info('ERROR CLIENT TELEGRAM');
                    return false;
                }

                TelegramApi::sendMessage($groupId, $answer);
            }
            return true;
        }

        return false;
    }

    public function setWebhook(Request $request) {
        $tokenBot = env('BOT_TOKEN');
        $hookUrl = $request->hook_url;
        $secret = $request->secret_key;
        if ($secret && $secret == env('SECRET_KEY')) {
            try {
                $telegram = new Telegram($tokenBot);
                $result = $telegram->setWebhook($hookUrl);
                if ($result->isOk()) {
                    echo $result->getDescription();
                }
            } catch (TelegramException $e) {
                // log telegram errors
                echo $e->getMessage();
            }
        } else {
            echo 'Invalid secret key';
        }
    }

    public function deleteWebhook(Request $request) {
        $tokenBot = env('BOT_TOKEN');
        $secret = $request->secret_key;
        if ($secret && $secret == env('SECRET_KEY')) {
            try {
                $telegram = new Telegram($tokenBot);
                $result = $telegram->deleteWebhook();
                if ($result->isOk()) {
                    echo $result->getDescription();
                }
            } catch (TelegramException $e) {
                echo $e->getMessage();
            }
        } else {
            echo 'Invalid secret key';
        }
    }
}
