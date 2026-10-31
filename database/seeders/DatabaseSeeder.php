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
    }
}
