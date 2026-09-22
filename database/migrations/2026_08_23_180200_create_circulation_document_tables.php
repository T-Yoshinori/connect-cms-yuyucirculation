<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCirculationDocumentTables extends Migration
{
    public function up()
    {
        Schema::create('yuyu_circulation_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('circulation_id');
            $table->integer('template_id')->nullable();
            $table->string('document_type', 32);
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->integer('applicant_user_id');
            $table->integer('applicant_section_id')->nullable();
            $table->string('approval_status', 32)->default('none');
            $table->string('circulation_status', 32)->default('none');
            $table->string('status', 32)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('circulation_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $this->auditColumns($table);
            $table->index(['circulation_id', 'applicant_user_id']);
            $table->index('template_id');
            $table->index('status');
            $table->index('approval_status');
            $table->index('circulation_status');
        });

        Schema::create('yuyu_circulation_steps', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('document_id');
            $table->integer('step_no');
            $table->string('action_type', 32);
            $table->integer('section_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->integer('approver_user_id')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('acted_at')->nullable();
            $table->text('comment')->nullable();
            $this->auditColumns($table);
            $table->index(['document_id', 'step_no']);
            $table->index(['approver_user_id', 'status']);
        });

        Schema::create('yuyu_circulation_targets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('document_id');
            $table->integer('user_id');
            $table->string('target_source', 32);
            $table->string('status', 32)->default('waiting');
            $table->timestamp('confirmed_at')->nullable();
            $this->auditColumns($table);
            $table->unique(['document_id', 'user_id'], 'circulation_targets_document_user_unique');
            $table->index(['user_id', 'status']);
        });

        Schema::create('yuyu_circulation_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('document_id');
            $table->integer('step_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('action', 50);
            $table->text('comment')->nullable();
            $table->integer('created_id')->nullable();
            $table->string('created_name', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['document_id', 'created_at']);
            $table->index('step_id');
            $table->index('user_id');
        });

        Schema::create('yuyu_circulation_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('document_id');
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('directory', 255)->nullable();
            $table->string('mime_type', 255)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->integer('uploaded_user_id')->nullable();
            $this->auditColumns($table);
            $table->index('document_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('yuyu_circulation_files');
        Schema::dropIfExists('yuyu_circulation_histories');
        Schema::dropIfExists('yuyu_circulation_targets');
        Schema::dropIfExists('yuyu_circulation_steps');
        Schema::dropIfExists('yuyu_circulation_documents');
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
