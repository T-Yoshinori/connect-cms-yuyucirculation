<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class YuyuCirculationPluginSeeder extends Seeder
{
    public function run()
    {
        if (!DB::table('plugins')->where('plugin_name', 'YuyuCirculation')->exists()) {
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
}
