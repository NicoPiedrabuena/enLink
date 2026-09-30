<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVisit extends Model
{
    public $timestamps = false;

    protected $fillable = ['visited_at', 'referrer_domain', 'user_agent_family'];

    protected function casts(): array
    {
        return ['visited_at' => 'datetime'];
    }

    public function pagePublication(): BelongsTo
    {
        return $this->belongsTo(PagePublication::class);
    }
}
