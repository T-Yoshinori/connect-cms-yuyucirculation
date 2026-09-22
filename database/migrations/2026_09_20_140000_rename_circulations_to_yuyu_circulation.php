<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenameCirculationsToYuyuCirculation extends Migration
{
    private const TABLES = [
        'circulations' => 'yuyu_circulations',
        'circulation_frames' => 'yuyu_circulation_frames',
        'circulation_templates' => 'yuyu_circulation_templates',
        'circulation_template_steps' => 'yuyu_circulation_template_steps',
        'circulation_template_targets' => 'yuyu_circulation_template_targets',
        'circulation_documents' => 'yuyu_circulation_documents',
        'circulation_steps' => 'yuyu_circulation_steps',
        'circulation_targets' => 'yuyu_circulation_targets',
        'circulation_histories' => 'yuyu_circulation_histories',
        'circulation_files' => 'yuyu_circulation_files',
        'circulation_questions' => 'yuyu_circulation_questions',
        'circulation_choices' => 'yuyu_circulation_choices',
        'circulation_answers' => 'yuyu_circulation_answers',
    ];

    public function up()
    {
        foreach (self::TABLES as $old_table => $new_table) {
            if (Schema::hasTable($old_table) && !Schema::hasTable($new_table)) {
                Schema::rename($old_table, $new_table);
            }
        }

        if (Schema::hasTable('plugins')) {
            $old_plugin = DB::table('plugins')->where('plugin_name', 'Circulations');
            if ($old_plugin->exists()) {
                if (DB::table('plugins')->where('plugin_name', 'YuyuCirculation')->exists()) {
                    $old_plugin->delete();
                } else {
                    $old_plugin->update(['plugin_name' => 'YuyuCirculation']);
                }
            }
        }

        if (Schema::hasTable('buckets')) {
            DB::table('buckets')
                ->where('plugin_name', 'circulations')
                ->update(['plugin_name' => 'yuyucirculation']);
        }

        if (Schema::hasTable('frames')) {
            DB::table('frames')
                ->where('plugin_name', 'circulations')
                ->update(['plugin_name' => 'yuyucirculation']);
        }
    }

    public function down()
    {
        if (Schema::hasTable('frames')) {
            DB::table('frames')
                ->where('plugin_name', 'yuyucirculation')
                ->update(['plugin_name' => 'circulations']);
        }

        if (Schema::hasTable('buckets')) {
            DB::table('buckets')
                ->where('plugin_name', 'yuyucirculation')
                ->update(['plugin_name' => 'circulations']);
        }

        if (Schema::hasTable('plugins')) {
            DB::table('plugins')
                ->where('plugin_name', 'YuyuCirculation')
                ->update(['plugin_name' => 'Circulations']);
        }

        foreach (array_reverse(self::TABLES, true) as $old_table => $new_table) {
            if (Schema::hasTable($new_table) && !Schema::hasTable($old_table)) {
                Schema::rename($new_table, $old_table);
            }
        }
    }
}
