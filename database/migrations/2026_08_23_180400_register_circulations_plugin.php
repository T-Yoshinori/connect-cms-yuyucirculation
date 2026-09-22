<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Keep the historical class name because Laravel resolves it from the migration filename.
class RegisterCirculationsPlugin extends Migration
{
    public function up()
    {
        // 既存サイトへの追加用。
        // 新規インストールでは migrations 実行時点で plugins が空のため、Seeder 側で登録する。
        if (DB::table('plugins')->count() > 0
            && !DB::table('plugins')->where('plugin_name', 'YuyuCirculation')->exists()) {
            DB::table('plugins')->insert([
                'plugin_name' => 'YuyuCirculation',
                'plugin_name_full' => '回覧・決裁',
                'display_flag' => 1,
                'display_sequence' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('plugins')->where('plugin_name', 'YuyuCirculation')->delete();
    }
}
