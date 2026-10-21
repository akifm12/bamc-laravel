<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Companies — seller Peppol / legal registration fields
        Schema::table('companies', function (Blueprint $table) {
            $table->string('tin', 20)->nullable()->after('trn');                        // first 10 digits of Corporate Tax TRN
            $table->string('seller_legal_reg_type', 10)->nullable()->after('tin');      // TL / EID / PAS / CD
        });

        // Customers — buyer legal registration fields
        Schema::table('customers', function (Blueprint $table) {
            $table->string('buyer_legal_reg_id')->nullable()->after('trn');             // trade license / EID / passport no.
            $table->string('buyer_legal_reg_type', 10)->nullable()->after('buyer_legal_reg_id'); // TL / EID / PAS / CD
        });

        // Invoices — missing mandatory header fields
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('transaction_type_flags', 8)->default('00000000')->after('invoice_type'); // 8-flag binary
            $table->string('payment_means_code', 10)->nullable()->after('payment_terms');            // 10=cash,30=bank,48=card
            $table->string('seller_legal_reg_id')->nullable()->after('supplier_trn');
            $table->string('seller_legal_reg_type', 10)->nullable()->after('seller_legal_reg_id');
            $table->string('buyer_legal_reg_id')->nullable()->after('customer_trn');
            $table->string('buyer_legal_reg_type', 10)->nullable()->after('buyer_legal_reg_id');
        });

        // Invoice lines — missing mandatory line fields
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->string('unit_of_measure', 20)->default('PCE')->after('quantity');
            $table->string('tax_category_code', 5)->default('S')->after('vat_rate');   // S / Z / E / O
            $table->string('tax_exemption_reason')->nullable()->after('tax_category_code');
            $table->decimal('item_gross_price', 15, 4)->nullable()->after('unit_price'); // before discount
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['tin', 'seller_legal_reg_type']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['buyer_legal_reg_id', 'buyer_legal_reg_type']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['transaction_type_flags', 'payment_means_code',
                'seller_legal_reg_id', 'seller_legal_reg_type',
                'buyer_legal_reg_id', 'buyer_legal_reg_type']);
        });
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['unit_of_measure', 'tax_category_code', 'tax_exemption_reason', 'item_gross_price']);
        });
    }
};
