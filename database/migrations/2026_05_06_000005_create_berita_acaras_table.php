<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita_acaras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('first_party_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('second_party_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('verification_token', 32)->unique();
            $table->string('location')->default('Bengkalis');
            $table->string('asset_condition')->nullable();
            $table->text('handover_statement')->nullable();
            $table->text('closing_statement')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_acaras');
    }
};
