<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailUrlToYuyuCirculationNotifications extends Migration
{
    public function up()
    {
        Schema::table('yuyu_circulation_notifications', function (Blueprint $table) {
            $table->text('detail_url')->nullable()->after('message');
        });
    }

    public function down()
    {
        Schema::table('yuyu_circulation_notifications', function (Blueprint $table) {
            $table->dropColumn('detail_url');
        });
    }
}
