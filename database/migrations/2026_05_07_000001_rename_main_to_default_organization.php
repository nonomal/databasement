<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->renameColumn('is_main', 'is_default');
        });

        DB::table('organizations')->where('is_default', true)->update(['name' => 'Default']);
    }

    public function down(): void
    {
        DB::table('organizations')->where('is_default', true)->update(['name' => 'Main']);

        Schema::table('organizations', function (Blueprint $table) {
            $table->renameColumn('is_default', 'is_main');
        });
    }
};
