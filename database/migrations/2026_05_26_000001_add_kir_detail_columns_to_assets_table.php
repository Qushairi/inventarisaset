<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->after('image_path');
            $table->string('size')->nullable()->after('serial_number');
            $table->string('material')->nullable()->after('size');
            $table->unsignedSmallInteger('acquisition_year')->nullable()->after('acquisition_price');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'serial_number',
                'size',
                'material',
                'acquisition_year',
            ]);
        });
    }
};
