<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('loan_letter_number')->nullable()->after('status_note');
            $table->longText('loan_letter_svg')->nullable()->after('loan_letter_number');
            $table->timestamp('loan_letter_generated_at')->nullable()->after('loan_letter_svg');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'loan_letter_number',
                'loan_letter_svg',
                'loan_letter_generated_at',
            ]);
        });
    }
};
