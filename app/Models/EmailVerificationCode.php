<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmailVerificationCode extends Model
{
    protected $fillable = [
        'email',
        'code',
        'type',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include codes that have not expired.
     *
     * @param  Builder<static>  $query
     */
    public function scopeValid(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }
}
