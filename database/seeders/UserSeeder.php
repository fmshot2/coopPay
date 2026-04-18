<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        $admin = User::create([
            'member_id'             => 'COOP-000',
            'name'                  => 'Super Admin',
            'email'                 => 'admin@cooppay.test',
            'phone'                 => '08100000000',
            'password'              => Hash::make('password'),
            'is_active'             => true,
            'must_change_password'  => false,
            'division_id'          => 1,
        ]);
        $admin->assignRole('admin');

        // Create 10 Members
        $members = [
            ['name' => 'Aliyu Musa',        'email' => 'aliyu@cooppay.test',    'phone' => '08101000001'],
            ['name' => 'Fatima Bello',       'email' => 'fatima@cooppay.test',   'phone' => '08101000002'],
            ['name' => 'Chukwuemeka Obi',   'email' => 'emeka@cooppay.test',    'phone' => '08101000003'],
            ['name' => 'Aisha Abdullahi',   'email' => 'aisha@cooppay.test',    'phone' => '08101000004'],
            ['name' => 'Tunde Adeyemi',     'email' => 'tunde@cooppay.test',    'phone' => '08101000005'],
            ['name' => 'Ngozi Okonkwo',     'email' => 'ngozi@cooppay.test',    'phone' => '08101000006'],
            ['name' => 'Ibrahim Yakubu',    'email' => 'ibrahim@cooppay.test',  'phone' => '08101000007'],
            ['name' => 'Blessing Eze',      'email' => 'blessing@cooppay.test', 'phone' => '08101000008'],
            ['name' => 'Musa Garba',        'email' => 'musa@cooppay.test',     'phone' => '08101000009'],
            ['name' => 'Chioma Nwosu',      'email' => 'chioma@cooppay.test',   'phone' => '08101000010'],
        ];

        foreach ($members as $index => $member) {
            $user = User::create([
                'member_id'             => 'COOP-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'name'                  => $member['name'],
                'email'                 => $member['email'],
                'phone'                 => $member['phone'],
                'password'              => Hash::make('password'),
                'is_active'             => true,
                'must_change_password'  => true,
                'division_id'          => 1,
            ]);
            $user->assignRole('member');
        }
    }
}
