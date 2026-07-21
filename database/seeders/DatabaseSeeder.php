<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Driver;
use App\Models\Toda;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        User::firstOrCreate(
            ['email' => 'superadmin@trifair.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'role' => 'superadmin',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@trifair.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $toda1 = Toda::firstOrCreate(
            ['name' => 'Brgy. San Antonio TODA'],
            ['location' => 'Sampaloc, Manila']
        );

        $toda2 = Toda::firstOrCreate(
            ['name' => 'Brgy. 456 TODA'],
            ['location' => 'Tondo, Manila']
        );

        $driver1User = User::firstOrCreate(
            ['email' => 'jayster@trifair.com'],
            [
                'name' => 'Jayster Cabay',
                'password' => Hash::make('driver123'),
                'role' => 'driver',
                'phone' => '09171234567',
                'is_active' => true,
            ]
        );

        Driver::firstOrCreate(
            ['user_id' => $driver1User->id],
            [
                'toda_id' => $toda1->id,
                'license_number' => 'N01-12345678',
                'plate_number' => 'ABC 1234',
                'body_number' => '001',
                'tricycle_color' => 'Red',
                'contact_number' => '09171234567',
                'address' => '123 Sampaloc, Manila',
                'qr_code' => 'WB2rxaPOZEfcA4RtcnKbGJZ14cu3LVjE',
                'status' => 'active',
            ]
        );

        $driver2User = User::firstOrCreate(
            ['email' => 'marcos@trifair.com'],
            [
                'name' => 'Marcos Reyes',
                'password' => Hash::make('driver123'),
                'role' => 'driver',
                'phone' => '09181234567',
                'is_active' => true,
            ]
        );

        Driver::firstOrCreate(
            ['user_id' => $driver2User->id],
            [
                'toda_id' => $toda2->id,
                'license_number' => 'N01-87654321',
                'plate_number' => 'XYZ 5678',
                'body_number' => '002',
                'tricycle_color' => 'Blue',
                'contact_number' => '09181234567',
                'address' => '456 Tondo, Manila',
                'qr_code' => Str::random(32),
                'status' => 'active',
            ]
        );

        $driver3User = User::firstOrCreate(
            ['email' => 'pedro@trifair.com'],
            [
                'name' => 'Pedro Santos',
                'password' => Hash::make('driver123'),
                'role' => 'driver',
                'phone' => '09191234567',
                'is_active' => true,
            ]
        );

        Driver::firstOrCreate(
            ['user_id' => $driver3User->id],
            [
                'toda_id' => $toda1->id,
                'license_number' => 'N01-11223344',
                'plate_number' => 'DEF 9012',
                'body_number' => '003',
                'tricycle_color' => 'White',
                'contact_number' => '09191234567',
                'address' => '789 Sampaloc, Manila',
                'qr_code' => Str::random(32),
                'status' => 'active',
            ]
        );
    }
}
