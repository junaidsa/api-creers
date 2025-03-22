<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if the column exists before dropping it
            if (Schema::hasColumn('users', 'language')) {
                $table->dropColumn('language');
            }

            // Add a new JSON column to store multiple languages
            $table->json('language_id')->nullable()->after('status');
        });

        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'language')) {
                $table->dropColumn('language');
            }

            // Add a new JSON column to store multiple languages
            $table->json('language_id')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('language_id'); // Remove the JSON column
            $table->string('language')->nullable(); // Restore original column if needed
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('language_id'); // Remove the JSON column
            $table->string('language')->nullable(); // Restore original column if needed
        });
    }
};
