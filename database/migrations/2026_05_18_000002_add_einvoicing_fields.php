<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('einvoicing_enabled')->default(false)->after('website');
            $table->string('einvoicing_provider')->nullable()->after('einvoicing_enabled'); // 'wafeq'
            $table->text('einvoicing_api_key')->nullable()->after('einvoicing_provider');   // encrypted
            $table->string('einvoicing_seller_id')->nullable()->after('einvoicing_api_key'); // Wafeq contact UUID for the seller
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('einvoice_uuid')->nullable()->after('notes');
            $table->text('einvoice_qr_code')->nullable()->after('einvoice_uuid');
            $table->string('einvoice_status')->nullable()->after('einvoice_qr_code'); // SUBMITTED / FAILED
            $table->timestamp('einvoice_submitted_at')->nullable()->after('einvoice_status');
            $table->text('einvoice_error')->nullable()->after('einvoice_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['einvoicing_enabled','einvoicing_provider','einvoicing_api_key','einvoicing_seller_id']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['einvoice_uuid','einvoice_qr_code','einvoice_status','einvoice_submitted_at','einvoice_error']);
        });
    }
};
