<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;

class CtlProductosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('ctl_productos')->insert([
            'nombre' => 'producto 1',
            'precio' => 1.5,
            'image' => 'image.url',
            'categoria_id' => 1,
        ]);

        DB::table('ctl_productos')->insert([
            'nombre' => 'producto 2',
            'precio' => 1.35,
            'image' => 'image.url',
            'categoria_id' => 2,
        ]);
    }
}
