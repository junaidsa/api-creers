<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('recruiter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->enum('status', ['active', 'expired', 'accepted', 'rejected'])->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};
