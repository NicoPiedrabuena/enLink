<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class LinkPage extends Model
{
    protected $fillable = ['username', 'display_name', 'bio', 'avatar_path', 'theme', 'active_publication_id', 'status', 'suspension_reason'];

    protected $appends = ['avatar_url'];

    protected function casts(): array
    {
        return ['theme' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class)->orderBy('position');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(PagePublication::class);
    }

    public function activePublication(): BelongsTo
    {
        return $this->belongsTo(PagePublication::class, 'active_publication_id');
    }
}
