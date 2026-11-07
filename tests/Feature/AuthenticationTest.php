<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_temporary_password_requires_change_before_accessing_the_app(): void
    {
        $student = User::where('email', 'student001@example.com')->firstOrFail();
        $student->forceFill([
            'password' => 'temporary-account-password',
            'must_change_password' => true,
        ])->save();

        $this->post('/login', [
            'email' => $student->email,
            'password' => 'temporary-account-password',
        ])->assertRedirect(route('password.change'));

        $this->get(route('student.dashboard'))->assertRedirect(route('password.change'));
        $this->get(route('password.change'))->assertOk()->assertSee('Change your temporary password');

        $this->post(route('password.change.update'), [
            'current_password' => 'temporary-account-password',
            'password' => 'a-new-secure-account-password',
            'password_confirmation' => 'a-new-secure-account-password',
        ])->assertRedirect(route('dashboard'));

        $student->refresh();
        $this->assertFalse($student->must_change_password);
        $this->assertTrue(Hash::check('a-new-secure-account-password', $student->password));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $student->id,
            'action' => 'account.password_changed',
            'target_id' => $student->id,
            'metadata' => null,
        ]);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->post('/login', [
            'email' => $student->email,
            'password' => 'temporary-account-password',
        ])->assertSessionHasErrors('email');
        $this->post('/login', [
            'email' => $student->email,
            'password' => 'a-new-secure-account-password',
        ])->assertRedirect(route('student.dashboard'));
    }

    public function test_password_change_requires_current_password_confirmation_and_difference(): void
    {
        $student = User::where('email', 'student001@example.com')->firstOrFail();
        $student->forceFill([
            'password' => 'temporary-account-password',
            'must_change_password' => true,
        ])->save();

        $this->actingAs($student)
            ->from(route('password.change'))
            ->post(route('password.change.update'), [
                'current_password' => 'wrong-password',
                'password' => 'a-new-secure-account-password',
                'password_confirmation' => 'a-new-secure-account-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($student)
            ->from(route('password.change'))
            ->post(route('password.change.update'), [
                'current_password' => 'temporary-account-password',
                'password' => 'temporary-account-password',
                'password_confirmation' => 'temporary-account-password',
            ])
            ->assertSessionHasErrors('password');
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
