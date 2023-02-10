<?php


namespace App\Http\Controllers;

use App\Helpers\TelegramApi;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Illuminate\Support\Facades\Cache;

class GroupController extends Controller
{
    public function __construct()
    {
//        $this->middleware('jwt-auth');
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
//        $currentUser = auth()->user();
//        if (!$currentUser) {
//            return $this->responseError();
//        }
        $group = Group::where('id_telegram', $groupId)->first();

        if ($group) {
            $dataUpdate = $request->all();
            $arrKeyUpdate = ['time_delay', 'active'];
            foreach ($arrKeyUpdate as $key) {
                if (array_key_exists($key, $dataUpdate)) {
                    $group[$key] = $dataUpdate[$key];
                }
            }
            //TODO: update time_next_run
            $group->save();
            return $this->responseSuccess();
        }

        return $this->responseError();
    }
}
