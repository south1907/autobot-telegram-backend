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
        'type_send',
        'current_index',
        'active2',
        'time_delay2',
        'time_nex_run2',
        'type_send2',
        'current_index2',
    ];

    public function items()
    {
        return $this->belongsToMany(Item::class);
    }

    public function type1_items() {
        return $this->items()->where('type','=', 1);
    }

    public function type2_items() {
        return $this->items()->where('type','=', 2);
    }
}
