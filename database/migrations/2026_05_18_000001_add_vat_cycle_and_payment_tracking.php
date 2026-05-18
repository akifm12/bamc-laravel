<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // 1 = Jan/Apr/Jul/Oct, 2 = Feb/May/Aug/Nov, 3 = Mar/Jun/Sep/Dec
            $table->tinyInteger('vat_quarter_start_month')->default(1)->after('vat_scheme');
        });

        Schema::table('vat_returns', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 2)->default(0)->after('box13_net_payable');
            $table->date('payment_date')->nullable()->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('vat_quarter_start_month');
        });
        Schema::table('vat_returns', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'payment_date']);
        });
    }
};
