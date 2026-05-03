<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_returns')) {
            return;
        }

        Schema::create('asset_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('returned_at');
            $table->string('verified_note')->nullable();
            $table->string('condition');
            $table->string('status');
            $table->string('status_note')->nullable();
            $table->string('report_number')->unique();
            $table->string('report_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_returns');
    }
};
