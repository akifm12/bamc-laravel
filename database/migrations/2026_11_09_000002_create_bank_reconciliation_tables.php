<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('account_id');
            $table->date('period_from');
            $table->date('period_to');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance_statement', 15, 2)->default(0);
            $table->string('status', 20)->default('OPEN'); // OPEN, LOCKED
            $table->text('notes')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by_id')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reconciliation_id');
            $table->unsignedBigInteger('company_id');
            $table->date('transaction_date');
            $table->string('description', 500)->nullable();
            $table->string('reference', 200)->nullable();
            $table->decimal('debit_amount', 15, 2)->default(0);  // money out
            $table->decimal('credit_amount', 15, 2)->default(0); // money in
            $table->boolean('matched')->default(false);
            $table->unsignedBigInteger('journal_line_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_reconciliations');
    }
};
