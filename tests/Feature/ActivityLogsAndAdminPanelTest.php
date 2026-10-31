<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Waypoint;
use App\Models\AppointmentRequest;
use App\Models\Consultation;
use App\Models\Feedback;
use App\Models\ActivityLog;
use App\Models\KioskSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivityLogsAndAdminPanelTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_test@mit.edu'],
            [
                'name' => 'Admin Boss',
                'role' => 'admin',
                'password' => bcrypt('password123'),
                'availability_status' => 'available',
            ]
        );

        $this->staff = User::firstOrCreate(
            ['email' => 'staff_test@mit.edu'],
            [
                'name' => 'John Doe',
                'title' => 'Mr.',
                'role' => 'faculty',
                'password' => bcrypt('password123'),
                'availability_status' => 'available',
            ]
        );
    }

    public function test_admin_adding_updating_and_deleting_waypoint_creates_logs(): void
    {
        $this->actingAs($this->admin);

        // Add
        $res = $this->post(route('admin.waypoints.store'), [
            'name' => 'Test Waypoint Lab',
            'location_code' => 'WP-TEST-99',
            'description' => 'Test description',
            'floor' => '2',
            'coordinates_x' => 100,
            'coordinates_y' => 200,
        ]);
        $res->assertSessionHasNoErrors();

        $waypoint = Waypoint::where('location_code', 'WP-TEST-99')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'add_waypoint',
            'description' => 'Admin User Added a New Waypoint - Test Waypoint Lab.',
        ]);

        // Update
        $res = $this->post(route('admin.waypoints.update', $waypoint), [
            'name' => 'Test Waypoint Lab Updated',
            'location_code' => 'WP-TEST-99',
            'description' => 'Updated description',
            'floor' => '2',
            'coordinates_x' => 110,
            'coordinates_y' => 210,
        ]);
        $res->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update_waypoint',
            'description' => 'Admin User Updated a Waypoint - Test Waypoint Lab Updated.',
        ]);

        // Delete
        $res = $this->delete(route('admin.waypoints.destroy', $waypoint));
        $res->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'delete_waypoint',
            'description' => 'Admin User Deleted a Waypoint - Test Waypoint Lab Updated.',
        ]);
    }

    public function test_admin_updating_and_deleting_staff_and_users_logs_correct_names_and_titles(): void
    {
        $this->actingAs($this->admin);

        // Create staff with title
        $newStaff = User::create([
            'name' => 'John Doe',
            'title' => 'Mr.',
            'email' => 'johndoe_temp@mit.edu',
            'password' => bcrypt('password123'),
            'role' => 'faculty',
        ]);

        // Update staff
        $res = $this->post(route('admin.users.update', $newStaff), [
            'name' => 'John Doe',
            'title' => 'Mr.',
            'email' => 'johndoe_temp@mit.edu',
            'role' => 'faculty',
            'availability_status' => 'available',
        ]);
        $res->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update_user',
            'description' => 'Admin User Updated a Staff - Mr. John Doe.',
        ]);

        // Delete staff
        $res = $this->delete(route('admin.users.destroy', $newStaff));
        $res->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'delete_user',
            'description' => 'Admin User Deleted a Staff - Mr. John Doe.',
        ]);
    }

    public function test_admin_updating_service_hours_logs_action(): void
    {
        $this->actingAs($this->admin);

        $res = $this->post(route('admin.settings.update'), [
            'service_hours_start' => '07:30',
            'service_hours_end' => '17:30',
            'service_days' => 'Monday - Friday',
            'kiosk_title' => 'Campus Guide - MIT Test',
            'kiosk_status' => 'online',
            'contact_email' => 'test@mit-maps.com',
        ]);
        $res->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update_service_hours',
            'description' => 'Admin User Updated a Service Hours',
        ]);
    }

    public function test_admin_updating_and_deleting_feedback(): void
    {
        $this->actingAs($this->admin);

        $feedback = Feedback::create([
            'rating' => 'Satisfied',
            'suggestions' => 'Great kiosk interface',
        ]);

        // Update
        $res = $this->put(route('admin.feedbacks.update', $feedback), [
            'rating' => 'Very Satisfied',
            'suggestions' => 'Even better now!',
        ]);
        $res->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update_feedback',
            'description' => "Admin User Updated a Feedback - ID #{$feedback->id}.",
        ]);

        // Delete
        $res = $this->delete(route('admin.feedbacks.destroy', $feedback));
        $res->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'delete_feedback',
            'description' => "Admin User Deleted a Feedback - ID #{$feedback->id}.",
        ]);
    }

    public function test_mobile_login_logout_status_and_password_logs(): void
    {
        // 1. Staff login
        $loginRes = $this->postJson('/api/mobile/login', [
            'email' => $this->staff->email,
            'password' => 'password123',
        ]);
        $loginRes->assertStatus(200);
        $token = $loginRes->json('token');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'login',
            'user_id' => $this->staff->id,
        ]);

        // 2. Staff status change
        $statusRes = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/mobile/profile/availability', [
                'availability_status' => 'busy',
            ]);
        $statusRes->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'change_status',
            'user_id' => $this->staff->id,
        ]);

        // 3. Staff password change
        $pwdRes = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/mobile/profile/password', [
                'current_password' => 'password123',
                'new_password' => 'newpassword123',
            ]);
        $pwdRes->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'change_password',
            'user_id' => $this->staff->id,
        ]);

        // 4. Staff logout
        $logoutRes = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/mobile/logout');
        $logoutRes->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'logout',
            'user_id' => $this->staff->id,
        ]);

        // 5. Admin login on mobile
        $adminLoginRes = $this->postJson('/api/mobile/login', [
            'email' => $this->admin->email,
            'password' => 'password123',
        ]);
        $adminLoginRes->assertStatus(200);
        $adminToken = $adminLoginRes->json('token');
        $this->assertEquals('admin', $adminLoginRes->json('user.role'));

        // Admin logout on mobile
        $adminLogoutRes = $this->withHeaders(['Authorization' => "Bearer {$adminToken}"])
            ->postJson('/api/mobile/logout');
        $adminLogoutRes->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'logout',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_mobile_admin_can_change_staff_password_and_forgot_password_sends_email(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        // Admin logs in on mobile
        $adminLoginRes = $this->postJson('/api/mobile/login', [
            'email' => $this->admin->email,
            'password' => 'password123',
        ]);
        $adminLoginRes->assertStatus(200);
        $adminToken = $adminLoginRes->json('token');

        // 1. Admin changes staff password via mobile admin panel
        $updateRes = $this->withHeaders(['Authorization' => "Bearer {$adminToken}"])
            ->postJson("/api/mobile/admin/users/{$this->staff->id}", [
                'name' => $this->staff->name,
                'title' => 'Mr.',
                'email' => $this->staff->email,
                'role' => $this->staff->role,
                'time_availability' => '8:00 AM - 5:00 PM',
                'availability_status' => 'available',
                'password' => 'staffnewpass123',
            ]);
        $updateRes->assertStatus(200);

        // Verify staff can log in with the new password set by Admin
        $staffLogin = $this->postJson('/api/mobile/login', [
            'email' => $this->staff->email,
            'password' => 'staffnewpass123',
        ]);
        $staffLogin->assertStatus(200);

        // 2. Staff requests Forgot Password on mobile login screen
        $forgotRes = $this->postJson('/api/mobile/forgot-password', [
            'email' => $this->staff->email,
        ]);
        $forgotRes->assertStatus(200)
            ->assertJson(['ok' => true]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\AccountRecoveryMail::class, function ($mail) {
            return $mail->hasTo($this->staff->email) && !empty($mail->newPassword);
        });

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'forgot_password',
            'user_id' => $this->staff->id,
        ]);
    }
}
