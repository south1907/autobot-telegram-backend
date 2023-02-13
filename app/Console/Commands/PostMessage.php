<?php

namespace App\Console\Commands;

use App\Helpers\CoreBot;
use App\Helpers\MessageHelper;
use App\Helpers\TelegramApi;
use App\Models\Group;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;

class PostMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post message to group';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // get groups active and check send message to group
        $groups = Group::where('active', 1)->get();
        $currentDate = Carbon::now();
        $tokenBot = env('BOT_TOKEN');

        if (!$tokenBot) {
            Log::error("not set token bot");
            $this->error("not set token bot");
            return false;
        }
        foreach($groups as $group) {
            if ($group->time_next_run != null && $group->time_next_run < $currentDate) {
                // thoi gian time_next_run < thoi gian hien tai --> thuc hien gui tin nhan tu dong
                Log::info("Send auto message to group: " . $group->name);
                $this->info("Send auto message to group: " . $group->name);

                try {
                    new Telegram($tokenBot);
                } catch (TelegramException $e) {
                    info('ERROR CLIENT TELEGRAM');
                    return false;
                }

                //TODO:get items and send it
                $text = 'hello';
                $message = [
                    'text'  =>  $text,
                    'reply_markup'  =>  null
                ];

                TelegramApi::sendMessage($group->id_telegram, $message);

                // update time_next_run
                $group->time_next_run = $currentDate->addSeconds($group->time_delay)->toDateTimeString();
                $group->save();
            }
        }
        $this->info('Done');
        Log::info("Done");
    }
}
