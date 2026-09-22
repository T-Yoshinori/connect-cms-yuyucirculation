<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYuyuCirculationNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('yuyu_circulation_notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('document_id');
            $table->string('notification_type', 32);
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('mail_sent_at')->nullable();
            $table->text('mail_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at'], 'yuyu_circulation_notifications_user_read_index');
            $table->index(['document_id', 'notification_type'], 'yuyu_circulation_notifications_document_type_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('yuyu_circulation_notifications');
    }
}
