<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('website');
            $table->string('bank_account_title')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_title');
            $table->string('bank_iban')->nullable()->after('bank_account_number');
            $table->string('bank_swift')->nullable()->after('bank_iban');
            $table->string('logo_path')->nullable()->after('bank_swift');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_title', 'bank_account_number', 'bank_iban', 'bank_swift', 'logo_path']);
        });
    }
};
