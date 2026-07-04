<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'nip')) {
            if (! Schema::hasIndex('users', ['nip'], 'unique')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique('nip');
                });
            }

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 30)->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'nip')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', ['nip'], 'unique')) {
                $table->dropUnique(['nip']);
            }

            $table->dropColumn('nip');
        });
    }
};
