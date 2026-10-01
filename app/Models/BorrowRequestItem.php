<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowRequestItem extends Model
{
    protected $fillable = ['borrow_request_id', 'item_id', 'quantity', 'return_condition'];
}
