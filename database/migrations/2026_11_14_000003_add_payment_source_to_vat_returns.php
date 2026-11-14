<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE vat_returns ADD COLUMN IF NOT EXISTS payment_source varchar(20)');
        DB::statement('ALTER TABLE vat_returns ADD COLUMN IF NOT EXISTS bank_amount numeric(15,2) DEFAULT 0');
        DB::statement('ALTER TABLE vat_returns ADD COLUMN IF NOT EXISTS surplus_used numeric(15,2) DEFAULT 0');
        DB::statement('ALTER TABLE vat_returns ADD COLUMN IF NOT EXISTS payment_journal_entry_id integer REFERENCES journal_entries(id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE vat_returns DROP COLUMN IF EXISTS payment_source');
        DB::statement('ALTER TABLE vat_returns DROP COLUMN IF EXISTS bank_amount');
        DB::statement('ALTER TABLE vat_returns DROP COLUMN IF EXISTS surplus_used');
        DB::statement('ALTER TABLE vat_returns DROP COLUMN IF EXISTS payment_journal_entry_id');
    }
};
