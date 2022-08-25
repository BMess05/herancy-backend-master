<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocialIdFieldInUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('social_id')->nullable()->after('id');
            $table->tinyInteger('social_type')->nullable()->after('social_id')->comment('1 for facebook, 2 for google, 3 for instagram and 4 for twitter');
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
            $table->dropColumn('social_id');
            $table->dropColumn('social_type');
        });
    }
}
