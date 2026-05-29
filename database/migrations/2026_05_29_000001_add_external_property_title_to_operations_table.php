<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExternalPropertyTitleToOperationsTable extends Migration
{
    public function up()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->string('external_property_title')->nullable()->after('property_id');
        });
    }

    public function down()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropColumn('external_property_title');
        });
    }
}
