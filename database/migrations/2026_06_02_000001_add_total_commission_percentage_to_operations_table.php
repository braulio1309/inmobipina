<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->decimal('total_commission_percentage', 5, 2)
                ->default(5)
                ->after('notes');
        });
    }

    public function down()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropColumn('total_commission_percentage');
        });
    }
};
