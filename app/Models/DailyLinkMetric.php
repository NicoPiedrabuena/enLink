<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyLinkMetric extends Model
{
    protected $fillable = ['page_publication_id', 'link_key', 'link_title', 'metric_date', 'clicks'];

    protected function casts(): array
    {
        return ['metric_date' => 'date'];
    }
}
