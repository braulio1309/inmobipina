<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('agent_id')
                ->nullable()
                ->after('approved_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_id');
        });
    }
};