<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->foreignId('owner_client_id')->nullable()->after('property_id')->constrained('clients')->nullOnDelete();
            $table->foreignId('buyer_client_id')->nullable()->after('owner_client_id')->constrained('clients')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_client_id');
            $table->dropConstrainedForeignId('buyer_client_id');
        });
    }
};