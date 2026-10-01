<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventorySession extends Model
{
    use HasFactory;

    public const OPEN = 'open';            // الجرد جارٍ والأرصدة مجمّدة
    public const COMPLETED = 'completed';  // سُوّيت الفروقات بانتظار الاعتماد
    public const APPROVED = 'approved';    // اعتمده رئيس القسم وأُغلقت الجلسة

    protected $fillable = ['title', 'created_by', 'approved_by', 'status', 'decision', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function inventorySessionItems()
    {
        return $this->hasMany(InventorySessionItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::OPEN, self::COMPLETED]);
    }
}
