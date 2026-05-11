<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('operations', function (Blueprint $table) {
            // Stores the full property price on a reservation so we can compute
            // the net sale amount (property_price - reservation_amount) when the
            // reservation is later confirmed as a sale.
            $table->decimal('property_price', 12, 2)->nullable()->after('amount');

            // Preserves the commission amounts earned during the reservation phase.
            // When a reservation is confirmed as a sale the new sale commissions are
            // ADDED on top of these values so total earnings always include both phases.
            $table->decimal('reservation_company_commission', 12, 2)->default(0)->after('property_price');
        });

        Schema::table('operation_user', function (Blueprint $table) {
            $table->decimal('reservation_commission_amount', 12, 2)->default(0)->after('commission_amount');
        });
    }

    public function down()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropColumn(['property_price', 'reservation_company_commission']);
        });

        Schema::table('operation_user', function (Blueprint $table) {
            $table->dropColumn('reservation_commission_amount');
        });
    }
};
