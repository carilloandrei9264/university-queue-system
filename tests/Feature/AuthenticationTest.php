<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_can_open_the_local_demo_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Local demo accounts only')
            ->assertSee('name="email"', false);
    }

    public function test_a_demo_student_can_sign_in_and_reaches_the_student_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => ' STUDENT001@EXAMPLE.COM ',
            'password' => 'testing-only-password',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'student001@example.com')->firstOrFail());
        $this->get(route('student.dashboard'))->assertOk()->assertSee('Student dashboard');
    }

    public function test_each_role_is_sent_to_its_own_dashboard(): void
    {
        $this->post('/login', [
            'email' => 'registrar.staff@example.com',
            'password' => 'testing-only-password',
        ])->assertRedirect(route('staff.dashboard'));

        $this->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Registrar');

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'testing-only-password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_users_cannot_access_another_roles_dashboard(): void
    {
        $dashboards = [
            'student' => route('student.dashboard'),
            'staff' => route('staff.dashboard'),
            'admin' => route('admin.dashboard'),
        ];

        foreach ($dashboards as $role => $allowedDashboard) {
            $user = User::where('role', $role)->firstOrFail();

            foreach ($dashboards as $dashboard) {
                if ($dashboard === $allowedDashboard) {
                    continue;
                }

                $this->actingAs($user)
                    ->get($dashboard)
                    ->assertForbidden();
            }
        }
    }

    public function test_staff_without_a_department_cannot_open_the_staff_dashboard(): void
    {
        $staff = User::where('role', 'staff')->firstOrFail();
        $staff->forceFill(['department_id' => null])->save();

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertForbidden();
    }

    public function test_deactivated_users_cannot_access_a_role_dashboard(): void
    {
        $student = User::where('role', 'student')->firstOrFail();
        $student->forceFill(['is_active' => false])->save();

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertForbidden();
    }

    public function test_invalid_inactive_or_passwordless_users_cannot_sign_in(): void
    {
        User::where('email', 'student001@example.com')->update(['is_active' => false]);
        User::where('email', 'student002@example.com')->update(['password' => null]);

        foreach (['student001@example.com', 'student002@example.com'] as $email) {
            $this->post('/login', [
                'email' => $email,
                'password' => 'testing-only-password',
            ])->assertSessionHasErrors('email');
        }

        $this->assertGuest();
    }

    public function test_demo_password_login_is_unavailable_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get(route('login'))->assertServiceUnavailable();
    }
}
