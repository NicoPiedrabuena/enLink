<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_page_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_publication_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');
            $table->unsignedBigInteger('visits')->default(0);
            $table->timestamps();

            $table->unique(['page_publication_id', 'metric_date']);
        });

        Schema::create('daily_link_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_publication_id')->constrained()->cascadeOnDelete();
            $table->string('link_key', 80);
            $table->string('link_title', 100);
            $table->date('metric_date');
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamps();

            $table->unique(['page_publication_id', 'link_key', 'metric_date'], 'daily_link_metric_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_link_metrics');
        Schema::dropIfExists('daily_page_metrics');
    }
};
