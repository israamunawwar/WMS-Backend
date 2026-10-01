<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name_en',
        'name_ar',
        'barcode',
        'initial_balance',
        'current_stock',
        'category_id',
        'location_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventorySessionItems()
    {
        return $this->hasMany(InventorySessionItem::class);
    }
}
