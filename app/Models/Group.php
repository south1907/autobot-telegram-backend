<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'groups';

    protected $fillable = [
        'name',
        'id_telegram',
        'bot_id_telegram',
        'active',
        'is_use_sticker_custom',
        'comment_check_flag',
        'comment_notify_flag',
        'comment_check_threshold',
        'forward_check_flag',
        'forward_notify_flag',
        'forward_check_threshold',
        'link_check_flag',
        'link_notify_flag',
        'link_check_threshold',
        'hidden_join_flag',
        'hello_join_flag',
        'ignore_link_list',
        'ignore_nickname_list',
        'ignore_channel_list',
        'content_not_allow',
        'is_use_not_allow_system',
        'audio_check_flag',
        'gif_check_flag',
        'image_check_flag',
        'sticker_check_flag',
        'video_check_flag',
        'file_check_flag'
    ];

}
