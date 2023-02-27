<?php


namespace App\Http\Controllers;

use App\Helpers\TelegramApi;
use App\Models\Group;
use App\Models\Item;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function __construct()
    {
    }
    public function list(Request $request) {

        if ($request->has('type') && $request->type == 1 || $request->type == 2) {
            $items = Item::where('type', $request->type)->get();
        } else {
            $items = Item::all();
        }

        if ($items) {
            return $this->responseSuccess($items);
        }
        return $this->responseError();
    }

    public function create(Request $request) {
        $this->middleware('jwt-auth');
        $currentUser = auth()->user();
        if (!$currentUser) {
            return $this->responseError();
        }

        $userIdTelegram = $currentUser->id_telegram;
        if ($currentUser->is_admin == 0) {
            return $this->responseError('Không có quyền');
        }
        $data = $request->all();

        $rules = [
            'type' => 'required|int'
        ];
        $validator = Validator::make($data,$rules);

        if ($validator->fails()) {
            return $this->responseError('Kiểm tra lại các trường bắt buộc');
        }
        try{
            $item = new Item();
            $listField = ['name', 'description', 'image', 'type', 'link'];
            $flagCheck = false;
            foreach ($listField as $key) {
                if (array_key_exists($key, $data)) {
                    $item[$key] = $data[$key];
                    if ($key != 'type') {
                        $flagCheck = true;
                    }
                }
            }
            if ($flagCheck) {
                $item->id_telegram = $userIdTelegram;
                $item->save();
            } else {
                return $this->responseError('Kiểm tra lại các trường bắt buộc');
            }
        }
        catch(Exception $e){
            return $this->responseError();
        }

        return $this->responseSuccess();
    }

    public function delete(Request $request, $itemId) {
        $this->middleware('jwt-auth');
        $currentUser = auth()->user();
        if (!$currentUser) {
            return $this->responseError();
        }

        if ($currentUser->is_admin == 0) {
            return $this->responseError('Không có quyền');
        }

        try{
            $item = Item::find($itemId);

            if ($item) {
                $item->delete();
                return $this->responseSuccess();
            }
        }
        catch(Exception $e){
            return $this->responseError();
        }

        return $this->responseError();
    }
}
