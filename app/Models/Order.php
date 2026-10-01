<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'destination', 'priority', 'status', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
