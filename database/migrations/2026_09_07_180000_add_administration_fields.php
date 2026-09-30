<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('password');
            $table->string('status', 20)->default('active')->after('role');
            $table->index(['role', 'status']);
        });

        Schema::table('link_pages', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('active_publication_id');
            $table->string('suspension_reason', 500)->nullable()->after('status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('link_pages', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'suspension_reason']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);
            $table->dropColumn(['role', 'status']);
        });
    }
};
