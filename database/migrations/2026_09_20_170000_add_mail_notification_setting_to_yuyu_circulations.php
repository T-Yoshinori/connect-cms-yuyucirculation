<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMailNotificationSettingToYuyuCirculations extends Migration
{
    public function up()
    {
        Schema::table('yuyu_circulations', function (Blueprint $table) {
            $table->boolean('mail_notification_enabled')
                ->default(true)
                ->after('name')
                ->comment('メール通知を使用する');
        });
    }

    public function down()
    {
        Schema::table('yuyu_circulations', function (Blueprint $table) {
            $table->dropColumn('mail_notification_enabled');
        });
    }
}
