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
        Schema::table('transaksis', function (Blueprint $table) {
            $table->boolean('is_reversal')->default(false)->after('keterangan');
            $table->foreignId('reversal_of_id')->nullable()->after('is_reversal')
                ->constrained('transaksis')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversal_of_id');
            $table->dropColumn('is_reversal');
        });
    }
};
