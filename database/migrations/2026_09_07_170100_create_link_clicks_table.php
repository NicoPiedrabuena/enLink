<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_publication_id')->constrained()->cascadeOnDelete();
            $table->string('link_key', 80);
            $table->string('link_title', 100);
            $table->timestamp('clicked_at');
            $table->string('referrer_domain')->nullable();
            $table->string('user_agent_family', 40)->nullable();

            $table->index(['page_publication_id', 'clicked_at']);
            $table->index(['page_publication_id', 'link_key', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_clicks');
    }
};
