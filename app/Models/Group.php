<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Item;

class Group extends Model
{
    use HasFactory;

    protected $table = 'groups';

    protected $fillable = [
        'name',
        'id_telegram',
        'user_id_telegram',
        'active',
        'time_delay',
        'time_nex_run',
    ];

    public function items()
    {
        return $this->belongsToMany(Item::class);
    }
}
