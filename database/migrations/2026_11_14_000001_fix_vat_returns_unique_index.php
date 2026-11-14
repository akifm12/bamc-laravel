<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The original index was on return_number alone, which breaks multi-company setups.
        // Replace with a composite unique on (company_id, return_number).
        DB::statement('DROP INDEX IF EXISTS ix_vat_returns_return_number');
        DB::statement('CREATE UNIQUE INDEX ix_vat_returns_return_number ON vat_returns (company_id, return_number)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ix_vat_returns_return_number');
        DB::statement('CREATE UNIQUE INDEX ix_vat_returns_return_number ON vat_returns (return_number)');
    }
};
