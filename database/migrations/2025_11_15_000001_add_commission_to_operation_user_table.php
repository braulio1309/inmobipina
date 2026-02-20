<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('operation_user', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)->default(0)->after('user_id');
            $table->decimal('commission_amount', 12, 2)->default(0)->after('commission_percentage');
        });

        Schema::table('operations', function (Blueprint $table) {
            $table->decimal('company_commission_percentage', 5, 2)->default(5)->after('notes');
            $table->decimal('company_commission_amount', 12, 2)->default(0)->after('company_commission_percentage');
        });
    }

    public function down()
    {
        Schema::table('operation_user', function (Blueprint $table) {
            $table->dropColumn(['commission_percentage', 'commission_amount']);
        });

        Schema::table('operations', function (Blueprint $table) {
            $table->dropColumn(['company_commission_percentage', 'company_commission_amount']);
        });
    }
};
