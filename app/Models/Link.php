<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Link extends Model
{
    protected $fillable = ['title', 'icon', 'icon_image_path', 'url', 'type', 'settings', 'position', 'is_active'];

    protected $appends = ['icon_image_url'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'settings' => 'array'];
    }

    public function getIconImageUrlAttribute(): ?string
    {
        return $this->icon_image_path ? Storage::disk('public')->url($this->icon_image_path) : null;
    }

    public function linkPage(): BelongsTo
    {
        return $this->belongsTo(LinkPage::class);
    }
}
