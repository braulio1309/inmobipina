<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('source')->nullable()->after('notes');
            $table->string('status')->default('potencial')->after('source');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('status');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['source', 'status', 'assigned_to']);
        });
    }
};
