<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_page_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('username', 30)->index();
            $table->json('payload');
            $table->string('status', 20)->default('published');
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['link_page_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_publications');
    }
};
