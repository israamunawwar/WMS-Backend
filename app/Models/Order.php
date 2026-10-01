<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const NEW = 'جديد';
    public const PENDING = 'بانتظار الاعتماد';
    public const APPROVED = 'تمت الموافقة';
    public const REJECTED = 'مرفوض';

    protected $fillable = ['user_id', 'destination', 'priority', 'status', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /** الطلبات التي تنتظر قراراً (جديدة أو بانتظار الاعتماد). */
    public function scopeAwaitingDecision(Builder $query): Builder
    {
        return $query->whereIn('status', [self::NEW, self::PENDING]);
    }

    public function isAwaitingDecision(): bool
    {
        return in_array($this->status, [self::NEW, self::PENDING], true);
    }
}
