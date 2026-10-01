<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['section', 'cabinet_number', 'shelf'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /** وصف مختصر للعرض: القسم - خزانة - رف. */
    public function getLabelAttribute(): string
    {
        return collect([$this->section, 'خزانة '.$this->cabinet_number, $this->shelf ? 'رف '.$this->shelf : null])
            ->filter()->implode(' - ');
    }
}
