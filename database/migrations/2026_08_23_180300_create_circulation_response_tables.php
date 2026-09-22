<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCirculationResponseTables extends Migration
{
    public function up()
    {
        Schema::create('yuyu_circulation_questions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('document_id');
            $table->integer('question_no');
            $table->string('question_type', 32);
            $table->text('question_text');
            $table->boolean('required')->default(false);
            $this->auditColumns($table);
            $table->index(['document_id', 'question_no']);
        });

        Schema::create('yuyu_circulation_choices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('question_id');
            $table->integer('choice_no');
            $table->text('choice_text');
            $this->auditColumns($table);
            $table->index(['question_id', 'choice_no']);
        });

        Schema::create('yuyu_circulation_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('document_id');
            $table->integer('question_id');
            $table->integer('user_id');
            $table->text('answer')->nullable();
            $this->auditColumns($table);
            $table->unique(['question_id', 'user_id'], 'circulation_answers_question_user_unique');
            $table->index(['document_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('yuyu_circulation_answers');
        Schema::dropIfExists('yuyu_circulation_choices');
        Schema::dropIfExists('yuyu_circulation_questions');
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
