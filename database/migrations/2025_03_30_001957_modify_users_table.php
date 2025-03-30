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

        Schema::table('users', function (Blueprint $table) {

            if (!Schema::hasColumn('users', 'notification_pref')) {
                $table->json('notification_pref')->nullable()->after('email');
            }
            if(!Schema::hasColumn('users', 'favourite_teams')) {
                $table->json('favourite_teams')->nullable()->after('email');
            }

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'notification_pref')) {
                $table->dropColumn('notification_pref');
            }
            if (Schema::hasColumn('users', 'favourite_teams')) {
                $table->dropColumn('favourite_teams');
            }
        });
    }
};
