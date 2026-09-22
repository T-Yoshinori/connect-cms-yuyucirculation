<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResponseVisibilityToCirculationDocuments extends Migration
{
    public function up()
    {
        Schema::table('yuyu_circulation_documents', function (Blueprint $table) {
            $table->string('response_visibility', 32)->default('targets')->after('circulation_status');
        });
    }

    public function down()
    {
        Schema::table('yuyu_circulation_documents', function (Blueprint $table) {
            $table->dropColumn('response_visibility');
        });
    }
}
