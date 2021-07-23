<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('firstname', 255);
            $table->string('lastname', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->string('phone_number', 255);
            $table->string('stripe_id', 255);
            $table->string('card_brand', 255);
            $table->string('card_last_four', 255);
            $table->string('stripe_id', 255);
            $table->string('pm_id', 255);
            $table->string('app_version', 255)->default('');
            $table->enum('role', ['admin', 'user'])->default('user');
            $table->enum('platform', ['ios', 'android', 'website'])->default('website');
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
        Schema::dropIfExists('users');
    }
}
