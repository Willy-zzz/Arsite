<?php

namespace Database\Seeders;

use App\Models\User;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate([
            'email' => 'admin@arsite.com'
        ], [
            'usu_nombre' => 'Cinthia',
            'email' => 'cdelacruz@arsite.com',
            'password' => Hash::make('admin123'),
            'usu_rol' => 'Administrador',
            'usu_estado' => 'Activo',
        ]);
    }
}
