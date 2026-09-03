<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Operator;
use App\Models\Toda;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Credentials are env-driven (or randomly generated) so no hardcoded
        // password ever ships in the codebase. Seeded accounts are printed to
        // the console once so they can still be used in local development.
        $mk = function (string $envKey) {
            $password = env($envKey);
            if ($password === null || $password === '') {
                $password = Str::random(16);
                $this->command->info("  !! No {$envKey} set — generated password: {$password}");
            }
            return $password;
        };

        $superadminPassword = $mk('SEED_SUPERADMIN_PASSWORD');

        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@trifair.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($superadminPassword),
            ]
        );
        $superadmin->forceFill(['role' => 'superadmin', 'is_active' => true])->save();

        $officerPassword = $mk('SEED_OFFICER_PASSWORD');

        $officer = User::firstOrCreate(
            ['email' => 'tfrbofficer@trifair.com'],
            [
                'name' => 'TFRB Officer',
                'password' => Hash::make($officerPassword),
            ]
        );
        $officer->forceFill(['role' => 'tfrb_officer', 'is_active' => true])->save();

        $toda1 = Toda::firstOrCreate(
            ['name' => 'Brgy. San Antonio TODA'],
            ['area' => 'Sampaloc, Manila']
        );

        $toda2 = Toda::firstOrCreate(
            ['name' => 'Brgy. 456 TODA'],
            ['area' => 'Tondo, Manila']
        );

        $operatorPassword = $mk('SEED_OPERATOR_PASSWORD');

        $driver1User = User::firstOrCreate(
            ['email' => 'jayster@trifair.com'],
            [
                'name' => 'Jayster Cabay',
                'password' => Hash::make($operatorPassword),
                'phone' => '09171234567',
            ]
        );
        $driver1User->forceFill(['role' => 'operator', 'is_active' => true])->save();

        Operator::firstOrCreate(
            ['user_id' => $driver1User->id],
            [
                'toda_id' => $toda1->id,
                'license_number' => 'N01-12345678',
                'plate_number' => 'ABC 1234',
                'body_number' => '001',
                'motorcycle_model' => 'Honda Wave 125',
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
                'password' => Hash::make($operatorPassword),
                'phone' => '09181234567',
            ]
        );
        $driver2User->forceFill(['role' => 'operator', 'is_active' => true])->save();

        Operator::firstOrCreate(
            ['user_id' => $driver2User->id],
            [
                'toda_id' => $toda2->id,
                'license_number' => 'N01-87654321',
                'plate_number' => 'XYZ 5678',
                'body_number' => '002',
                'motorcycle_model' => 'Yamaha Mio Sporty',
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
                'password' => Hash::make($operatorPassword),
                'phone' => '09191234567',
            ]
        );
        $driver3User->forceFill(['role' => 'operator', 'is_active' => true])->save();

        Operator::firstOrCreate(
            ['user_id' => $driver3User->id],
            [
                'toda_id' => $toda1->id,
                'license_number' => 'N01-11223344',
                'plate_number' => 'DEF 9012',
                'body_number' => '003',
                'motorcycle_model' => 'Suzuki Smash 115',
                'contact_number' => '09191234567',
                'address' => '789 Sampaloc, Manila',
                'qr_code' => Str::random(32),
                'status' => 'active',
            ]
        );

        $presidentPassword = $mk('SEED_PRESIDENT_PASSWORD');

        $presidentUser = User::firstOrCreate(
            ['email' => 'president@trifair.com'],
            [
                'name' => 'TODA President',
                'password' => Hash::make($presidentPassword),
                'phone' => '09161234567',
            ]
        );
        $presidentUser->forceFill([
            'role' => 'operator_president',
            'is_active' => true,
            'toda_id' => $toda1->id,
        ])->save();

        Operator::firstOrCreate(
            ['user_id' => $presidentUser->id],
            [
                'toda_id' => $toda1->id,
                'license_number' => 'N01-PRES-01',
                'plate_number' => 'PRES 001',
                'body_number' => 'P01',
                'motorcycle_model' => 'Honda Beat',
                'contact_number' => '09161234567',
                'address' => 'Sampaloc, Manila',
                'qr_code' => Str::random(32),
                'status' => 'active',
            ]
        );
    }
}
