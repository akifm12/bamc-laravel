<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ct_returns', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('fiscal_year');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('return_number', 30)->nullable();
            $table->string('accounting_basis', 10)->default('ACCRUAL');

            // Revenue
            $table->decimal('total_revenue', 20, 4)->default(0);

            // Accounting income (from P&L)
            $table->decimal('accounting_income', 20, 4)->default(0);

            // Taxable income after adjustments
            $table->decimal('total_additions', 20, 4)->default(0);
            $table->decimal('total_deductions', 20, 4)->default(0);
            $table->decimal('taxable_income', 20, 4)->default(0);

            // Tax calculation
            $table->decimal('income_at_zero_rate', 20, 4)->default(0);
            $table->decimal('income_at_nine_pct', 20, 4)->default(0);
            $table->decimal('tax_at_nine_pct', 20, 4)->default(0);
            $table->decimal('foreign_tax_credit', 20, 4)->default(0);
            $table->decimal('ct_payable', 20, 4)->default(0);

            // SBR
            $table->boolean('sbr_elected')->default(false);

            // Filing
            $table->string('status', 20)->default('DRAFT'); // DRAFT, FILED, PAID
            $table->date('filing_date')->nullable();
            $table->string('filing_reference', 100)->nullable();
            $table->decimal('amount_paid', 20, 4)->default(0);
            $table->date('payment_date')->nullable();

            $table->text('notes')->nullable();
            $table->integer('created_by_id')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'fiscal_year']);
        });

        Schema::create('ct_adjustments', function (Blueprint $table) {
            $table->id();
            $table->integer('ct_return_id');
            $table->integer('company_id');
            $table->string('type', 10); // ADD or LESS
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 20, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ct_adjustments');
        Schema::dropIfExists('ct_returns');
    }
};
