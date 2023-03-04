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
        foreach($groups as $group) {
            $currentDate = Carbon::now();
            if (
                ($type == 'type1_items' && $group->time_next_run != null && $group->time_next_run < $currentDate)
                || ($type == 'type2_items' && $group->time_next_run2 != null && $group->time_next_run2 < $currentDate)
            ) {
                // thoi gian time_next_run < thoi gian hien tai --> thuc hien gui tin nhan tu dong
                Log::info("Send auto message to group: " . $group->name);
                $this->info("Send auto message to group: " . $group->name);

                try {
                    new Telegram($tokenBot);
                } catch (TelegramException $e) {
                    info('ERROR CLIENT TELEGRAM');
                    return;
                }

                $currentIndex = 0;
                $maxItem = count($group[$type]);
                if ($group[$type] && $maxItem > 0) {
                    if ($type == 'type1_items') {
                        $typeSend = $group->type_send;
                        $currentIndex = $group->current_index;
                    } else {
                        $typeSend = $group->type_send2;
                        $currentIndex = $group->current_index2;
                    }

                    if ($typeSend == 1) {
                        // type random
                        $currentIndex = rand(0, $maxItem - 1);
                    }
                    Log::info("current_index: " . $currentIndex);
                    $this->info("current_index: " . $currentIndex);

                    $currentIndex = $currentIndex % $maxItem;
                    $firstItem = $group[$type][$currentIndex];

                    if ($firstItem->image) {
                        $messagePhoto = [
                            'image'  =>  $firstItem->image,
                            'title'  =>  $firstItem->name
                        ];
                        TelegramApi::sendPhoto($group->id_telegram, $messagePhoto);
                    }

                    // send type special
                    $resMedia = False;
                    if ($firstItem->type == 1) {
                        // video
                        if (!str_contains($firstItem->link, 'youtube.com')) {
                            Log::info("Send video: " . $group->name);
                            $this->info("Send video: " . $group->name);
                            $messageVideo = [
                                'video'  =>  $firstItem->link,
                                'title'  =>  $firstItem->name
                            ];
                            $resMedia = TelegramApi::sendVideo($group->id_telegram, $messageVideo);
                            if (!$resMedia) {
                                Log::info("Send video error: " . $group->name);
                                $this->info("Send video error: " . $group->name);
                            }
                        } else {
                            Log::info("Video youtube, not upload telegram: " . $group->name);
                            $this->info("Video youtube, not upload telegram: " . $group->name);
                        }
                    }

                    if ($firstItem->type == 2) {
                        // file
                        $messageFile = [
                            'file'  =>  $firstItem->link,
                            'title'  =>  $firstItem->name
                        ];
                        $resMedia = TelegramApi::sendDocument($group->id_telegram, $messageFile);
                        if (!$resMedia) {
                            Log::info("Send file error: " . $group->name);
                            $this->info("Send file error: " . $group->name);
                        }
                    }

                    $text = "";
                    if ($firstItem->description) {
                        $text .= $firstItem->description;
                    }

                    if ($firstItem->link && !$resMedia) {
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
                // update current_index
                if ($type == 'type1_items') {
                    if ($maxItem > 0) {
                        $group->current_index = ($currentIndex + 1) % $maxItem;
                    }
                    $group->time_next_run = $currentDate->addSeconds($group->time_delay)->toDateTimeString();
                } else {
                    if ($maxItem > 0) {
                        $group->current_index2 = ($currentIndex + 1) % $maxItem;
                    }
                    $group->time_next_run2 = $currentDate->addSeconds($group->time_delay2)->toDateTimeString();
                }
                $group->save();
            }
        }
    }
}
