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
        $tokenBot = env('BOT_TOKEN');
        if (!$tokenBot) {
            Log::error("not set token bot");
            $this->error("not set token bot");
            return false;
        }

        // get groups active and check send message to group
        $groups = Group::where('active', 1)->with('type1_items')->get();
        $this->processGroups($groups, 'type1_items', $tokenBot);

        $groups2 = Group::where('active2', 1)->with('type2_items')->get();
        $this->processGroups($groups2, 'type2_items', $tokenBot);

        $this->info('Done');
        Log::info("Done");
    }

    private function processGroups($groups, string $type, $tokenBot): void
    {
        $currentDate = Carbon::now();
        foreach($groups as $group) {
            if ($group->time_next_run != null && $group->time_next_run < $currentDate) {
                // thoi gian time_next_run < thoi gian hien tai --> thuc hien gui tin nhan tu dong
                Log::info("Send auto message to group: " . $group->name);
                $this->info("Send auto message to group: " . $group->name);

                try {
                    new Telegram($tokenBot);
                } catch (TelegramException $e) {
                    info('ERROR CLIENT TELEGRAM');
                    return;
                }

                //TODO:get items and send it
                if ($group[$type] && count($group[$type]) > 0) {
                    $firstItem = $group[$type][0];

                    if ($firstItem->image) {
                        $messagePhoto = [
                            'image'  =>  $firstItem->image,
                            'title'  =>  $firstItem->name
                        ];
                        TelegramApi::sendPhoto($group->id_telegram, $messagePhoto);
                    }

                    $text = "";
                    if ($firstItem->description) {
                        $text .= $firstItem->description;
                    }
                    if ($firstItem->link) {
                        $text .= "\n" . $firstItem->link;
                    }

                    if ($text != "") {
                        $message = [
                            'text'  =>  $text,
                            'reply_markup'  =>  null
                        ];

                        TelegramApi::sendMessage($group->id_telegram, $message);
                    }
                }

                // update time_next_run || time_next_run2
                if ($type == 'type1_items') {
                    $group->time_next_run = $currentDate->addSeconds($group->time_delay)->toDateTimeString();
                } else {
                    $group->time_next_run2 = $currentDate->addSeconds($group->time_delay2)->toDateTimeString();
                }
                $group->save();
            }
        }
    }
}
