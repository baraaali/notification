<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   //user_notification contain notifications for each user
        //user has many notification and one notification sent to many users
        Schema::create('user_notification', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->integer('notification_id')->unsigned();;
            //add user_id column as forign key
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
             //add notification_id column as forign key
             $table->foreign('notification_id')
             ->references('id')
             ->on('notifications')
             ->onDelete('cascade');

            //read to verify if user's notification marked as read
            //read =1 notification was read , read= 0 user doesn't read notification yet
            $table->integer('read');
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
        Schema::dropIfExists('user_notification');
    }
}
