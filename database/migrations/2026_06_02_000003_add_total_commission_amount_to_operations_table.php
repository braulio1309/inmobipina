<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->decimal('total_commission_amount', 12, 2)->default(0)->after('total_commission_percentage');
        });
    }

    public function down()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropColumn('total_commission_amount');
        });
    }
};
