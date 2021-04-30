<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NotificationUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('notification_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('notification_id')->unsigned();
            $table->integer('user_id')->unsigned();
             //read to verify if user's notification marked as read
            //read =1 notification was read , read= 0 user doesn't read notification yet
            $table->integer('read')->default(0);
             //add notification_id column as forign key
             $table->foreign('notification_id')
             ->references('id')
             ->on('notifications')
             ->onDelete('cascade');
              //add user_id column as forign key
            $table->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');


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
        Schema::dropIfExists('notification_user');
    }
}
