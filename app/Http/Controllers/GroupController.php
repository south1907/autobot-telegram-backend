<?php


namespace App\Http\Controllers;

use App\Helpers\TelegramApi;
use App\Models\Group;
use App\Models\Setting;
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

    public function list(Request $request) {
        $currentUser = auth()->user();
        if (!$currentUser) {
            return $this->responseError();
        }

        $userIdTelegram = $currentUser->id_telegram;
        $groups = Group::where('user_id_telegram', $userIdTelegram)->get();
        if ($groups) {
            return $this->responseSuccess($groups);
        }
        return $this->responseError();
    }

    public function index(Request $request) {

        $currentUser = auth()->user();
        if (!$currentUser) {
            return $this->responseError();
        }

        $groupId = $request->get('group_id');

        $group = Group::where('id_telegram', $groupId)->with('items')->first();
        if ($group) {
            if ($currentUser->id_telegram != $group->user_id_telegram) {
                return $this->responseError();
            }
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

            if ($userIdTelegram != $group->user_id_telegram) {
                return $this->responseError('Bạn không có quyền thực hiện');
            }

            $checkAdmin = TelegramApi::checkAdmin($groupId, $userIdTelegram);

            if (!$checkAdmin) {
                return $this->responseError('Bạn phải có quyền admin của group');
            }

            $dataUpdate = $request->all();
            $setting = Setting::where('type', 'SYSTEM')->first();
            if (array_key_exists('active', $dataUpdate)) {
                if ($dataUpdate['active'] == 0 || $dataUpdate['active'] == 1) {
                    $group['active'] = $dataUpdate['active'];
                }
            }

            if (array_key_exists('time_delay', $dataUpdate) && $dataUpdate['time_delay'] > 0) {
                if ($setting) {

                    if ($dataUpdate['time_delay'] < $setting->min_time_delay || $dataUpdate['time_delay'] > $setting->max_time_delay) {
                        return $this->responseError('Thời gian delay không phù hợp ('.$setting->min_time_delay.' < time < '.$setting->max_time_delay.')');
                    }
                }
                $group['time_delay'] = $dataUpdate['time_delay'];

                // update time_next_run
                $group['time_next_run'] = Carbon::now()->addSeconds($group->time_delay)->toDateTimeString();
            }

            if (array_key_exists('type_send', $dataUpdate)) {
                if ($dataUpdate['type_send'] == 0 || $dataUpdate['type_send'] == 1) {
                    $group['type_send'] = $dataUpdate['type_send'];
                }
            }

            if (array_key_exists('active2', $dataUpdate)) {
                if ($dataUpdate['active2'] == 0 || $dataUpdate['active2'] == 1) {
                    $group['active2'] = $dataUpdate['active2'];
                }
            }

            if (array_key_exists('time_delay2', $dataUpdate) && $dataUpdate['time_delay2'] > 0) {
                $group['time_delay2'] = $dataUpdate['time_delay2'];

                // update time_next_run
                $group['time_next_run2'] = Carbon::now()->addSeconds($group->time_delay2)->toDateTimeString();
            }

            if (array_key_exists('type_send2', $dataUpdate)) {
                if ($dataUpdate['type_send2'] == 0 || $dataUpdate['type_send2'] == 1) {
                    $group['type_send2'] = $dataUpdate['type_send2'];
                }
            }

            if (array_key_exists('items', $dataUpdate)) {
                $group->items()->sync($dataUpdate['items']);
            }

            $group->current_index = 0;
            $group->current_index2 = 0;

            $group->save();
            return $this->responseSuccess();
        }

        return $this->responseError();
    }

    public function getSetting(Request $request) {

        $currentUser = auth()->user();
        if (!$currentUser) {
            return $this->responseError();
        }

        if ($currentUser->is_admin == 0) {
            return $this->responseError();
        }

        $setting = Setting::where('type', 'SYSTEM')->first();
        if ($setting) {
            return $this->responseSuccess($setting);
        }
        return $this->responseError();
    }

    public function editSetting(Request $request) {
        $currentUser = auth()->user();
        if (!$currentUser) {
            return $this->responseError();
        }

        if ($currentUser->is_admin == 0) {
            return $this->responseError();
        }

        $setting = Setting::where('type', 'SYSTEM')->first();
        if ($setting) {
            $dataUpdate = $request->all();
            $listField = ['default_time_delay', 'min_time_delay', 'max_time_delay'];
            foreach ($listField as $key) {
                if (array_key_exists($key, $dataUpdate)) {
                    $setting[$key] = $dataUpdate[$key];
                }
            }
            $setting->save();
            return $this->responseSuccess();
        }

        return $this->responseError();
    }

}
