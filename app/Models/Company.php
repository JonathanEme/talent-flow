<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'tax_id',
        'email',
        'phone',
        'website',
        'description',
    ];

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }
}