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
        Schema::table('downloads', function (Blueprint $table) {
            $table->string('resolution')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropColumn('resolution');
        });
    }
};
