<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    public const PENDING = 'pending';      // بانتظار قرار اللجنة
    public const REPAIRING = 'repairing';  // قيد الصيانة
    public const FIXED = 'fixed';          // تم الإصلاح
    public const SCRAPPED = 'scrapped';    // أُتلفت نهائياً
    public const REPLACED = 'replaced';    // استُبدلت من المخزون

    public const LABELS = [
        self::PENDING => 'بانتظار القرار',
        self::REPAIRING => 'قيد الصيانة',
        self::FIXED => 'تم الإصلاح',
        self::SCRAPPED => 'أُتلفت نهائياً',
        self::REPLACED => 'استُبدلت',
    ];

    protected $fillable = ['item_id', 'reported_by', 'description', 'notes', 'status', 'cost'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /** السجلات التي لم تُغلق بالإصلاح (تالفة أو قيد المعالجة). */
    public function scopeUnresolved($query)
    {
        return $query->where('status', '!=', self::FIXED);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::LABELS[$this->status] ?? $this->status;
    }
}
