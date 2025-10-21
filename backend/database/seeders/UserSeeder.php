<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ep = [];
        $p='password';
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@agency.com',
            'password' => Hash::make($p),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);
        $ep[] = ['admin@agency.com',$p];
        
        User::create([
            'name' => 'Editor User',
            'email' => 'editor@agency.com',
            'password' => Hash::make($p),
            'role' => 'EDITOR',
            'is_active' => true,
        ]);
        $ep[] = ['editor@agency.com',$p];

        $this->command->info('Default users created successfully:');
        $headers = ['Email', 'Password'];
        $this->command->table($headers, $ep);
    }
}

