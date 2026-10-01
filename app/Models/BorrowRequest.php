<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowRequest extends Model
{
    protected $fillable = ['user_id', 'lab_id', 'status', 'expected_return_date', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }

    public function items()
    {
        return $this->hasMany(BorrowRequestItem::class);
    }
}
