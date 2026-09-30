<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('link_pages', function (Blueprint $table) {
            $table->foreignId('active_publication_id')
                ->nullable()
                ->after('theme')
                ->constrained('page_publications')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('link_pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_publication_id');
        });
    }
};
