<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiviceTokenAndAcceptNotification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   //add two colums at users table:device_token and accept_notification
        Schema::table('users', function (Blueprint $table) {
            //device_token to store tokens of devices to use it for push notification
            $table->longText('device_token')->nullable();
            //accept_notification for accept or not recieving notification
            //by default ==1 means notification accepted by user
            $table->integer('accept_notification')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
