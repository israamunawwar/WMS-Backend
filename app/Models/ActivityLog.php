<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    /** القسم الذي تنتمي إليه العملية (للعرض)، مفتاحه جزء من اسم العملية. */
    private const SECTIONS = [
        'order' => 'إدارة الطلبات',
        'inventory' => 'الجرد',
        'maintenance' => 'الصيانة',
        'damage' => 'الصيانة',
        'user' => 'المستخدمون',
        'setting' => 'الإعدادات',
        'category' => 'الأصناف',
    ];

    protected $fillable = ['user_id', 'action', 'description'];

    /** تسجيل عملية باسم المستخدم الحالي (أو بدون مستخدم إذا كانت من النظام). */
    public static function record(string $action, string $description): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getSectionAttribute(): string
    {
        foreach (self::SECTIONS as $key => $label) {
            if (str_contains($this->action, $key)) {
                return $label;
            }
        }

        return 'النظام';
    }

    /** لون العرض: أخضر للموافقات والإنجاز، أحمر للرفض والحذف والإتلاف، أزرق لغير ذلك. */
    public function getToneAttribute(): string
    {
        return match (true) {
            str_contains($this->action, 'reject'), str_contains($this->action, 'delete'),
            str_contains($this->action, 'scrap'), str_contains($this->action, 'damage') => 'red',
            str_contains($this->action, 'approve'), str_contains($this->action, 'close'),
            str_contains($this->action, 'fix') => 'green',
            default => 'blue',
        };
    }
}
