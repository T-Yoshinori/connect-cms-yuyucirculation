<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCirculationTemplateTables extends Migration
{
    public function up()
    {
        Schema::create('yuyu_circulation_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('circulation_id');
            $table->string('name', 255);
            $table->string('template_code', 100);
            $table->string('workflow_type', 32)->default('decision');
            $table->boolean('post_circulation_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $this->auditColumns($table);
            $table->unique(['circulation_id', 'template_code'], 'circulation_templates_code_unique');
            $table->index('circulation_id');
        });

        Schema::create('yuyu_circulation_template_steps', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('template_id');
            $table->integer('step_no');
            $table->string('section_mode', 32)->default('applicant_section');
            $table->integer('section_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->string('action_type', 32)->default('approval');
            $this->auditColumns($table);
            $table->index(['template_id', 'step_no']);
            $table->index('section_id');
            $table->index('group_id');
        });

        Schema::create('yuyu_circulation_template_targets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('template_id');
            $table->integer('sort_order')->default(0);
            $table->string('target_type', 32);
            $table->integer('section_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->integer('user_id')->nullable();
            $this->auditColumns($table);
            $table->index(['template_id', 'sort_order']);
            $table->index('section_id');
            $table->index('group_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('yuyu_circulation_template_targets');
        Schema::dropIfExists('yuyu_circulation_template_steps');
        Schema::dropIfExists('yuyu_circulation_templates');
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
