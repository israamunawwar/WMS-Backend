<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitItem extends Model
{
    protected $fillable = ['kit_id', 'item_id', 'quantity'];
}
