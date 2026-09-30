<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkClick extends Model
{
    public $timestamps = false;

    protected $fillable = ['link_key', 'link_title', 'clicked_at', 'referrer_domain', 'user_agent_family'];

    protected function casts(): array
    {
        return ['clicked_at' => 'datetime'];
    }

    public function pagePublication(): BelongsTo
    {
        return $this->belongsTo(PagePublication::class);
    }
}
