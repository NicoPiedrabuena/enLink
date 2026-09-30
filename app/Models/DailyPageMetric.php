<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPageMetric extends Model
{
    protected $fillable = ['page_publication_id', 'metric_date', 'visits'];

    protected function casts(): array
    {
        return ['metric_date' => 'date'];
    }
}
