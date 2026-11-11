<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // One and only Administrator account
        User::firstOrCreate(
            ['email' => 'admin@mit-maps.com'],
            [
                'name' => 'MIT Maps Administrator',
                'password' => Hash::make('mit_v3_987251_admin'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'mitportalservices@gmail.com'],
            [
                'name' => 'MIT Portal Admin',
                'title' => null,
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'availability_status' => 'available',
            ]
        );

        User::firstOrCreate(
            ['email' => 'bryecastro@gmail.com'],
            [
                'name' => 'Brye Castro',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // Faculty / Consultants / Counselor Accounts
        $faculty1 = User::firstOrCreate(
            ['email' => 'johndoe@mit-maps.com'],
            [
                'name' => 'Dr. John Doe',
                'title' => null,
                'password' => Hash::make('faculty_password_123'),
                'role' => 'faculty',
                'availability_status' => 'available',
            ]
        );

        $faculty2 = User::firstOrCreate(
            ['email' => 'janesmith@mit-maps.com'],
            [
                'name' => 'Prof. Jane Smith',
                'title' => null,
                'password' => Hash::make('faculty_password_123'),
                'role' => 'faculty',
                'availability_status' => 'busy',
            ]
        );

        $faculty3 = User::firstOrCreate(
            ['email' => 'dishrexd22@yahoo.com'],
            [
                'name' => 'Faculty Dishrexd',
                'title' => null,
                'password' => Hash::make('Shark123'),
                'role' => 'faculty',
                'availability_status' => 'available',
            ]
        );

        $faculty4 = User::firstOrCreate(
            ['email' => 'katemalona@gmail.com'],
            [
                'name' => 'Kate Malona',
                'title' => null,
                'password' => Hash::make('bayadnyodaw123'),
                'role' => 'faculty',
                'availability_status' => 'available',
            ]
        );

        $faculty5 = User::firstOrCreate(
            ['email' => 'alexanderalfonsoviii@gmail.com'],
            [
                'name' => 'Alexander Alfonso VIII',
                'title' => null,
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'availability_status' => 'available',
            ]
        );

        $principal = User::firstOrCreate(
            ['email' => 'principal@mit-maps.com'],
            [
                'name' => 'Dr. Albus Dumbledore',
                'title' => null,
                'password' => Hash::make('principal_password_123'),
                'role' => 'principal',
                'availability_status' => 'available',
            ]
        );

        $counselor = User::firstOrCreate(
            ['email' => 'counselor@mit-maps.com'],
            [
                'name' => 'Ms. Sarah Jenkins',
                'title' => null,
                'password' => Hash::make('counselor_password_123'),
                'role' => 'guidance_counselor',
                'availability_status' => 'available',
            ]
        );

        // Requested Faculty Accounts (password: admin123)
        $facultyAccounts = [
            ['name' => 'Jeanie L. Lambino',          'email' => 'jeanielambino@gmail.com'],
            ['name' => 'Emmarie D. Camajen',         'email' => 'emmariecamajen@gmail.com'],
            ['name' => 'Julie Ann Mangisel',         'email' => 'julieannmangisel@gmail.com'],
            ['name' => 'Chery Borromeo',             'email' => 'cheryborromeo@gmail.com'],
            ['name' => 'Shane Bergonia',             'email' => 'shanebergonia@gmail.com'],
            ['name' => 'Krisyl Ann Bucsit',          'email' => 'krisylannbucsit@gmail.com'],
            ['name' => 'Sharline Joyce Peralta',     'email' => 'sharlinejoyceperalta@gmail.com'],
            ['name' => 'Francis Ylarde',             'email' => 'francisylarde@gmail.com'],
            ['name' => 'Marcelo S. Manangan',        'email' => 'marcelomanangan@gmail.com'],
            ['name' => 'Maria Bianca Lydel Osquiza', 'email' => 'mariabiancalydelosquiza@gmail.com'],
            ['name' => 'Ma. Luisa Reyes',            'email' => 'maluisareyes@gmail.com'],
        ];

        foreach ($facultyAccounts as $acct) {
            User::updateOrCreate(
                ['email' => $acct['email']],
                [
                    'name' => $acct['name'],
                    'title' => null,
                    'password' => Hash::make('admin123'),
                    'role' => 'faculty',
                    'availability_status' => 'available',
                ]
            );
        }

        // Requested Guidance Counselor Account (password: admin123)
        User::updateOrCreate(
            ['email' => 'alexandernunez1414@gmail.com'],
            [
                'name' => 'Liezel V. Dimalibot',
                'title' => null,
                'password' => Hash::make('admin123'),
                'role' => 'guidance_counselor',
                'availability_status' => 'available',
            ]
        );

        // Waypoints with assigned staff (only seed initially if table is empty)
        if (\App\Models\Waypoint::count() === 0) {
            $lobby = \App\Models\Waypoint::create([
                'location_code' => 'WP-0001',
                'name' => 'Main Lobby',
                'description' => 'The main entrance lobby of the MIT campus. A central meeting point for visitors.',
                'floor' => '1',
                'coordinates_x' => 50,
                'coordinates_y' => 50,
                'pano_url' => 'https://pannellum.org/images/alma.jpg',
                'assigned_user_id' => $counselor->id,
            ]);
            $lobby->assignedUsers()->sync([$counselor->id]);

            $wing = \App\Models\Waypoint::create([
                'location_code' => 'WP-0002',
                'name' => 'Faculty Wing',
                'description' => 'The administrative hub where faculty offices and meeting rooms are located.',
                'floor' => '2',
                'coordinates_x' => 30,
                'coordinates_y' => 35,
                'pano_url' => 'https://pannellum.org/images/cerro-toco-0.jpg',
                'assigned_user_id' => $faculty1->id,
            ]);
            $wing->assignedUsers()->sync([$faculty1->id, $faculty2->id]);
        }

        // Ensure default Kiosk Settings exist so kiosk_settings table is populated
        $defaultSettings = [
            'service_hours_start' => '08:00',
            'service_hours_end'   => '17:00',
            'service_days'        => 'Monday - Friday',
            'kiosk_title'         => 'Campus Guide - MIT',
            'kiosk_status'        => 'online',
            'contact_email'       => 'mitportalservices@gmail.com',
        ];
        foreach ($defaultSettings as $key => $val) {
            \App\Models\KioskSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $val]
            );
        }
    }
}
