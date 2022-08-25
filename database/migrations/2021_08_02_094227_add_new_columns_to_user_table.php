<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->after('email');
            $table->string('country_code')->nullable()->after('phone_number');
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->integer('otp')->nullable()->after('country_code');
            $table->integer('profile_status')->default(0)->after('otp');
            $table->string('image')->nullable()->after('profile_status');
            $table->smallInteger('push_notification')->default(1)->after('image')->comment="0 = Off, 1 = ON";
            $table->softDeletes();
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
