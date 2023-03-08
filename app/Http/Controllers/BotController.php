<?php


namespace App\Http\Controllers;

use App\Helpers\CoreBot;
use App\Helpers\MessageHelper;
use App\Helpers\TelegramApi;
use App\Models\Group;
use App\Models\Item;
use App\Models\Setting;
use App\Models\Telegram\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use YouTube\Exception\YouTubeException;
use YouTube\YouTubeDownloader;

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
                    $group->active2 = 0;
                    $group->time_delay = 86400;
                    $group->time_delay2 = 86400;
                    $group->current_index = 0;
                    $group->current_index2 = 0;
                    $group->type_send = 0;
                    $group->type_send2 = 0;
                    $setting = Setting::where('type', 'SYSTEM')->first();
                    if ($setting && $setting->default_time_delay) {
                        $group->time_delay = $setting->default_time_delay;
                        $group->time_delay2 = $setting->default_time_delay;
                    }

                    $group->time_next_run = Carbon::now()->addSeconds($group->time_delay)->toDateTimeString();
                    $group->time_next_run2 = $group->time_next_run;

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
                $flagYoutube = false;

                $checkUser = User::where('id_telegram', $message->getUserId())->first();
                if ($checkUser != null && $checkUser->is_admin == 1) {
                    $arrUrl = $message->getArrUrl();
                    if ($arrUrl != null && count($arrUrl) > 0) {
                        foreach ($arrUrl as $url) {
                            if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                                $flagYoutube = true;
                                $answer = CoreBot::getAnswerCreateType($url);
                                break;
                            }
                        }
                    }
                } else {
                    info("not admin, not create type");
                }

                if (!$flagYoutube) {
                    // only get answer
                    $answer = CoreBot::getAnswer($message);
                }

            }

            if ($message->getType() == 'WITH_GROUP') {
                if ($message->getCommand() && $message->isSelfCommand()) {
                    // command
                    $answer = CoreBot::getAnswer($message);
                }
            }

            if ($message->getType() == 'CALLBACK') {
                $dataCallback = $message->getDataCallback();
                if (str_contains($dataCallback, 'youtube.com') || str_contains($dataCallback, 'youtu.be')) {
                    $dataCallback = str_replace($dataCallback, '&feature=share', '');
                    $dataCallback = str_replace($dataCallback, 'youtu.be/', 'youtube.com/watch?v=');

                    // check exists
                    $checkExists = Item::where('link', $dataCallback)->first();
                    if ($checkExists) {
                        $answer = CoreBot::getAnswerConfirm('Đã tạo trước đó');
                    } else {
                        $youtube = new YouTubeDownloader();
                        $titleYoutube = null;
                        try {
                            $downloadOptions = $youtube->getDownloadLinks($dataCallback);
                            $titleYoutube = $downloadOptions->getInfo()->getTitle();
                        } catch (YouTubeException $e) {
                            echo 'Something went wrong: ' . $e->getMessage();
                        }

                        // create Item
                        $item = new Item();
                        $item->name = $titleYoutube;
                        $item->type = 1;
                        $item->link = $dataCallback;
                        $item->id_telegram = $message->getUserId();
                        $item->save();
                        $answer = CoreBot::getAnswerConfirm('Tạo thành công');
                    }
                }
                if ($dataCallback == 'NONE') {
                    $answer = CoreBot::getAnswerConfirm('Không tạo');
                }
            }
            if ($answer) {
                try {
                    new Telegram($tokenBot);
                } catch (TelegramException $e) {
                    info('ERROR CLIENT TELEGRAM');
                    return false;
                }

                foreach ($answer as $iAnswer) {
                    if ($iAnswer['type'] == 'text') {
                        TelegramApi::sendMessage($groupId, $iAnswer['data']);
                    }
                    if ($iAnswer['type'] == 'image') {
                        TelegramApi::sendPhoto($groupId, $iAnswer['data']);
                    }
                }

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
