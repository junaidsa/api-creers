<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            // Modify skiles_ids to JSON type
            $table->json('skiles_ids')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            // Rollback to previous type (LONGTEXT)
            $table->longText('skiles_ids')->nullable()->change();
        });
    }
};

