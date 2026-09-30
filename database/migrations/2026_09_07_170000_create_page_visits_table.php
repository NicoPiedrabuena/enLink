<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_publication_id')->constrained()->cascadeOnDelete();
            $table->timestamp('visited_at');
            $table->string('referrer_domain')->nullable();
            $table->string('user_agent_family', 40)->nullable();

            $table->index(['page_publication_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
