<?php


namespace App\Http\Controllers;

use App\Helpers\CoreBot;
use App\Helpers\MessageHelper;
use App\Helpers\TelegramApi;
use App\Models\Group;
use App\Models\Telegram\Message;
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
