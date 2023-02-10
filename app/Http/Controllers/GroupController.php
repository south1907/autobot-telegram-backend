<?php


namespace App\Http\Controllers;

use App\Helpers\TelegramApi;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Illuminate\Support\Facades\Cache;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt-auth');
    }
    public function index(Request $request) {
        $groupId = $request->get('group_id');

        $group = Group::where('id_telegram', $groupId)->first();
        if ($group) {
            return $this->responseSuccess($group);
        }
        return $this->responseError();
    }

    public function update(Request $request, $groupId) {
        $currentUser = auth()->user();
        if (!$currentUser) {
            return $this->responseError();
        }
        $group = Group::where('id_telegram', $groupId)->first();

        if ($group) {
            try {
                $tokenBot = env('BOT_TOKEN');
                new Telegram($tokenBot);
            } catch (TelegramException $e) {
                info('ERROR CLIENT TELEGRAM');
                return $this->responseError();
            }

            $userIdTelegram = $currentUser->id_telegram;
            $checkAdmin = TelegramApi::checkAdmin($groupId, $userIdTelegram);

            if (!$checkAdmin) {
                return $this->responseError('Bạn phải có quyền admin của group');
            }

            $dataUpdate = $request->all();
            if (array_key_exists('active', $dataUpdate)) {
                if ($dataUpdate['active'] == 0 || $dataUpdate['active'] == 1) {
                    $group['active'] = $dataUpdate['active'];
                }
            }

            if (array_key_exists('time_delay', $dataUpdate) && $dataUpdate['time_delay'] > 0) {
                $group['time_delay'] = $dataUpdate['time_delay'];

                // update time_next_run
                $group['time_next_run'] = Carbon::now()->addSeconds($group->time_delay)->toDateTimeString();
            }

            //TODO: set list item

            $group->save();
            return $this->responseSuccess();
        }

        return $this->responseError();
    }
}
