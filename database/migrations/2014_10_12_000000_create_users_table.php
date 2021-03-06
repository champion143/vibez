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
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('birth_of_date')->nullable();
            $table->text('user_profile_images_url')->nullable();
            $table->text('username')->nullable();
            $table->text('zipcode')->nullable();
            // $table->text('mobile')->nullable();
            $table->integer('account_type')->default(0);
            $table->text('api_token')->nullable();
            $table->text('device_token')->nullable();
            $table->longtext('boi')->nullable();
            $table->integer('flag')->default(0);
            $table->rememberToken();
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
