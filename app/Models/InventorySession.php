<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// إضافة HasFactory لتمكين الـ seeding و Factories مستقبلاً
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventorySession extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'created_by', 'approved_by', 'status'];

    public function inventorySessionItems()
    {
        return $this->hasMany(InventorySessionItem::class);
    }
}
