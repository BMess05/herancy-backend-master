<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettings extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('system_settings')->insert([
            'key' => 'usd_to_kenya',
            'value' => '110.3704',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('system_settings')->insert([
            'key' => 'usd_to_india',
            'value' => '74.7555',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
