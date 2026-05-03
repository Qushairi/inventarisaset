<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loans')) {
            return;
        }

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('loan_date');
            $table->date('planned_return_date')->nullable();
            $table->string('status');
            $table->string('status_note')->nullable();
            $table->timestamps();

            $table->unique(['asset_id', 'user_id', 'loan_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
