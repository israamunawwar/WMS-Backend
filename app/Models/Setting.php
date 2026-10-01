<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /** القيم الافتراضية للإعدادات المعروفة. */
    public const DEFAULTS = [
        'low_stock_threshold' => 5,
        'default_borrow_days' => 14,
        'site_name' => 'نظام إدارة المستودعات',
    ];

    protected $fillable = ['key', 'value', 'type'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value, 'type' => $type]);
    }

    /** حد المخزون المنخفض: المواد التي رصيدها بين 1 وهذا الرقم تعتبر منخفضة. */
    public static function lowStockThreshold(): int
    {
        return max(1, (int) static::get('low_stock_threshold'));
    }
}
