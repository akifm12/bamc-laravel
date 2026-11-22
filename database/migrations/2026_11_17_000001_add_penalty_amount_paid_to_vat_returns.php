<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vat_returns', function (Blueprint $table) {
            $table->decimal('penalty_amount_paid', 15, 4)->default(0)->after('surplus_used');
            $table->unsignedBigInteger('penalty_clearing_account_id')->nullable()->after('penalty_amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('vat_returns', function (Blueprint $table) {
            $table->dropColumn(['penalty_amount_paid', 'penalty_clearing_account_id']);
        });
    }
};
