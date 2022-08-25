<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTablePaymentNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sender_id');
            $table->string('sender_phone');
            $table->bigInteger('receiver_id')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->float('amount', 8, 2);
            $table->string('notes')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_image')->nullable();
            $table->tinyInteger('payment_type')->default(0)->comment = "0 => send payment, 1 => request payment";
            $table->tinyInteger('notification_type')->comment = "1 => payment notification";
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
        Schema::dropIfExists('notifications');
    }
}
