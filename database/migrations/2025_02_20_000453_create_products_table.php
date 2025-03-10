<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->decimal('purchase_price')->nullable();
            $table->decimal('selling_price')->nullable();
            $table->integer('quantity')->nullable()->default(0);
            $table->unsignedBigInteger('security_stock')->nullable()->default(2);
            $table->boolean('active')->default(true)->comment('False si le stock est nul, True sinon');
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
