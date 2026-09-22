<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Keep the historical class name because Laravel resolves it from the migration filename.
class CreateCirculationsBaseTables extends Migration
{
    public function up()
    {
        Schema::create('yuyu_circulations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bucket_id')->comment('バケツID');
            $table->string('name', 255)->comment('回覧・決裁名');
            $this->auditColumns($table);
            $table->index('bucket_id');
        });

        Schema::create('yuyu_circulation_frames', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('circulation_id')->comment('回覧・決裁ID');
            $table->integer('frame_id')->comment('フレームID');
            $table->string('view_format', 32)->nullable()->comment('表示形式');
            $table->integer('view_count')->nullable()->comment('1ページの表示件数');
            $this->auditColumns($table);
            $table->index('circulation_id');
            $table->index('frame_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('yuyu_circulation_frames');
        Schema::dropIfExists('yuyu_circulations');
    }

    private function auditColumns(Blueprint $table)
    {
        $table->integer('created_id')->nullable();
        $table->string('created_name', 255)->nullable();
        $table->timestamp('created_at')->nullable();
        $table->integer('updated_id')->nullable();
        $table->string('updated_name', 255)->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->integer('deleted_id')->nullable();
        $table->string('deleted_name', 255)->nullable();
        $table->timestamp('deleted_at')->nullable();
    }
}
