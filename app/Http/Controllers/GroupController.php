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
        $this->middleware('jwt-auth');
    }
    public function index(Request $request) {
        $groupId = $request->get('group_id');

        $group = Group::where('id_telegram', $groupId)->first();
        $currentUser = auth()->user();

        return $this->responseError();
    }

    public function update(Request $request, $groupId) {
        $currentUser = auth()->user();
        if (!$currentUser) {
            return $this->responseError();
        }
        $group = Group::where('id_telegram', $groupId)->first();

        return $this->responseError();
    }
}
