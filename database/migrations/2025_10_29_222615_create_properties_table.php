<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('address');
            $table->integer('bathrooms')->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('square_meters');

            $table->enum('type_sale', ['venta', 'alquiler', 'ambos']);
            $table->decimal('price', 12, 2);
            $table->string('status')->default('pending');
            $table->boolean('exclusivity')->default(false);
            $table->decimal('map_lat', 10, 8)->nullable();
            $table->decimal('map_lng', 11, 8)->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('properties');
    }
};
