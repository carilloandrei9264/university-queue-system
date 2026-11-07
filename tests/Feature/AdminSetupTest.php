<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
    }

    public function test_admin_can_create_department_service_and_staff(): void
    {
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Departments');
        $this->get(route('admin.departments.index'))->assertOk()->assertSee('Add department');
        $this->get(route('admin.services.index'))->assertOk()->assertSee('Add service');
        $this->get(route('admin.staff.index'))->assertOk()->assertSee('Add staff account');

        $this->post(route('admin.departments.store'), [
            'name' => 'Library',
            'prefix' => 'LIB',
            'is_active' => 1,
        ])->assertRedirect(route('admin.departments.index'));

        $department = Department::where('prefix', 'LIB')->firstOrFail();

        $this->post(route('admin.services.store'), [
            'department_id' => $department->id,
            'name' => 'Book Loan',
            'is_active' => 1,
        ])->assertRedirect(route('admin.services.index'));

        $this->post(route('admin.staff.store'), [
            'full_name' => 'Library Staff',
            'email' => 'LIBRARY.STAFF@example.com',
            'department_id' => $department->id,
            'password' => 'temporary-demo-password',
            'password_confirmation' => 'temporary-demo-password',
            'is_active' => 1,
        ])->assertRedirect(route('admin.staff.index'));

        $staff = User::where('email', 'library.staff@example.com')->firstOrFail();
        $this->assertSame('staff', $staff->role);
        $this->assertSame($department->id, $staff->department_id);
        $this->assertTrue(Hash::check('temporary-demo-password', $staff->password));
        $this->assertDatabaseHas('services', [
            'department_id' => $department->id,
            'name' => 'Book Loan',
        ]);
    }

    public function test_admin_can_edit_and_deactivate_catalog_and_staff_records(): void
    {
        $department = Department::where('prefix', 'AMS')->firstOrFail();
        $service = Service::where('department_id', $department->id)->firstOrFail();
        $staff = User::where('role', 'staff')->where('department_id', $department->id)->firstOrFail();

        $this->patch(route('admin.departments.update', $department), [
            'name' => 'Admissions',
            'prefix' => 'AMS',
            'is_active' => 0,
        ])->assertRedirect(route('admin.departments.index'));

        $this->patch(route('admin.services.update', $service), [
            'department_id' => $department->id,
            'name' => 'Updated service',
            'is_active' => 0,
        ])->assertRedirect(route('admin.services.index'));

        $this->patch(route('admin.staff.update', $staff), [
            'full_name' => 'Updated Staff',
            'email' => $staff->email,
            'department_id' => $department->id,
            'password' => '',
            'password_confirmation' => '',
            'is_active' => 0,
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Admissions', 'is_active' => 0]);
        $this->assertDatabaseHas('services', ['id' => $service->id, 'name' => 'Updated service', 'is_active' => 0]);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'full_name' => 'Updated Staff', 'is_active' => 0]);
    }

    public function test_catalog_and_staff_forms_reject_invalid_values(): void
    {
        $this->from(route('admin.departments.index'))
            ->post(route('admin.departments.store'), [
                'name' => 'Invalid prefix office',
                'prefix' => 'bad',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('prefix');

        $this->from(route('admin.departments.index'))
            ->post(route('admin.departments.store'), [
                'name' => 'Duplicate prefix office',
                'prefix' => 'AMS',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('prefix');

        $inactiveDepartment = Department::create([
            'name' => 'Inactive Office',
            'prefix' => 'INA',
            'is_active' => false,
        ]);

        $this->from(route('admin.staff.index'))
            ->post(route('admin.staff.store'), [
                'full_name' => 'Unassigned Staff',
                'email' => 'unassigned@example.com',
                'department_id' => $inactiveDepartment->id,
                'password' => 'temporary-demo-password',
                'password_confirmation' => 'temporary-demo-password',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('department_id');

        $this->assertDatabaseMissing('users', ['email' => 'unassigned@example.com']);
    }

    public function test_service_cannot_be_moved_to_another_department_after_tickets_exist(): void
    {
        $service = Service::firstOrFail();
        $student = User::where('role', 'student')->firstOrFail();
        $ticket = Ticket::create([
            'department_id' => $service->department_id,
            'service_id' => $service->id,
            'student_id' => $student->id,
            'queue_date' => now()->toDateString(),
            'number' => 1,
            'code' => 'AMS-001',
            'status' => 'waiting',
        ]);

        $otherDepartment = Department::where('id', '!=', $service->department_id)->firstOrFail();

        $this->from(route('admin.services.index'))
            ->patch(route('admin.services.update', $service), [
                'department_id' => $otherDepartment->id,
                'name' => $service->name,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('department_id');

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'department_id' => $service->department_id]);
    }

    public function test_non_admin_roles_cannot_open_admin_pages_or_submit_admin_actions(): void
    {
        $routes = [
            route('admin.departments.index'),
            route('admin.services.index'),
            route('admin.staff.index'),
        ];

        foreach (['student', 'staff'] as $role) {
            $user = User::where('role', $role)->firstOrFail();

            foreach ($routes as $route) {
                $this->actingAs($user)->get($route)->assertForbidden();
            }

            $this->actingAs($user)
                ->post(route('admin.departments.store'), [
                    'name' => 'Unauthorized Office',
                    'prefix' => 'UNX',
                    'is_active' => 1,
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('departments', ['prefix' => 'UNX']);
    }

    public function test_staff_management_cannot_create_or_edit_an_admin(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->post(route('admin.staff.store'), [
            'full_name' => 'Attempted Admin',
            'email' => 'attempted.admin@example.com',
            'department_id' => Department::firstOrFail()->id,
            'password' => 'temporary-demo-password',
            'password_confirmation' => 'temporary-demo-password',
            'is_active' => 1,
            'role' => 'admin',
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertDatabaseMissing('users', ['email' => 'attempted.admin@example.com', 'role' => 'admin']);

        $this->patch(route('admin.staff.update', $admin), [
            'full_name' => $admin->full_name,
            'email' => $admin->email,
            'department_id' => Department::firstOrFail()->id,
            'password' => '',
            'password_confirmation' => '',
            'is_active' => 1,
        ])->assertNotFound();

        $this->assertSame(1, User::where('role', 'admin')->count());
    }

    public function test_admin_can_create_update_and_deactivate_passwordless_student_records(): void
    {
        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Add student record')
            ->assertSee('No password is set')
            ->assertSee('Search by name, email, or student ID');

        $this->post(route('admin.students.store'), [
            'full_name' => 'New Student',
            'email' => ' NEW.STUDENT@example.com ',
            'student_id' => '  DEMO-2026-004 ',
            'course' => 'BS Information Technology',
            'is_active' => 1,
            'role' => 'admin',
            'password' => 'should-not-be-saved',
        ])->assertRedirect(route('admin.students.index'));

        $student = User::where('email', 'new.student@example.com')->firstOrFail();
        $this->assertSame('student', $student->role);
        $this->assertSame('DEMO-2026-004', $student->student_id);
        $this->assertNull($student->password);
        $this->assertNull($student->email_verified_at);
        $this->assertFalse(Auth::attempt([
            'email' => $student->email,
            'password' => 'should-not-be-saved',
        ]));

        $this->patch(route('admin.students.update', $student), [
            'full_name' => 'Updated Student',
            'email' => 'new.student@example.com',
            'student_id' => 'DEMO-2026-004',
            'course' => 'BS Computer Science',
            'is_active' => 0,
            'role' => 'admin',
            'password' => 'also-ignored',
        ])->assertRedirect(route('admin.students.index'));

        $student->refresh();
        $this->assertSame('student', $student->role);
        $this->assertSame('Updated Student', $student->full_name);
        $this->assertSame('BS Computer Science', $student->course);
        $this->assertFalse($student->is_active);
        $this->assertNull($student->password);
    }

    public function test_student_administration_rejects_duplicate_identity_and_non_admin_access(): void
    {
        $student = User::where('role', 'student')->firstOrFail();

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), [
                'full_name' => 'Duplicate Student',
                'email' => 'other@example.com',
                'student_id' => $student->student_id,
                'course' => 'BS Information Technology',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('student_id');

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.store'), [
                'full_name' => 'Duplicate Student',
                'email' => $student->email,
                'student_id' => 'DEMO-DUPLICATE',
                'course' => 'BS Information Technology',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('email');

        foreach (['student', 'staff'] as $role) {
            $user = User::where('role', $role)->firstOrFail();

            $this->actingAs($user)
                ->get(route('admin.students.index'))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('admin.students.store'), [
                    'full_name' => 'Unauthorized Student',
                    'email' => 'unauthorized.student@example.com',
                    'student_id' => 'UNAUTHORIZED-001',
                    'is_active' => 1,
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('users', ['email' => 'unauthorized.student@example.com']);
    }

    public function test_admin_can_search_student_records_without_exposing_other_roles(): void
    {
        $this->get(route('admin.students.index', ['search' => 'student001']))
            ->assertOk()
            ->assertSee('student001@example.com')
            ->assertDontSee('student002@example.com')
            ->assertDontSee('admission.staff@example.com');
    }

    public function test_student_management_cannot_change_a_non_student_role(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->patch(route('admin.students.update', $admin), [
            'full_name' => $admin->full_name,
            'email' => $admin->email,
            'student_id' => 'NOT-A-STUDENT',
            'is_active' => 1,
        ])->assertNotFound();

        $this->assertSame(1, User::where('role', 'admin')->count());
    }

    public function test_first_admin_command_creates_only_one_terminal_admin(): void
    {
        User::where('role', 'admin')->delete();

        $this->artisan('queue-it:create-admin')
            ->expectsQuestion('Administrator full name', 'Project Owner')
            ->expectsQuestion('Administrator email', 'owner@example.com')
            ->expectsQuestion('Password (minimum 12 characters)', 'a-strong-local-password')
            ->expectsQuestion('Confirm password', 'a-strong-local-password')
            ->expectsOutputToContain('Administrator account created')
            ->assertExitCode(0);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $this->assertSame('admin', $owner->role);
        $this->assertTrue(Hash::check('a-strong-local-password', $owner->password));

        $this->artisan('queue-it:create-admin')
            ->expectsOutputToContain('An administrator already exists')
            ->assertExitCode(1);

        $this->assertSame(1, User::where('role', 'admin')->count());
    }

    public function test_first_admin_command_rejects_a_weak_password(): void
    {
        User::where('role', 'admin')->delete();

        $this->artisan('queue-it:create-admin')
            ->expectsQuestion('Administrator full name', 'Project Owner')
            ->expectsQuestion('Administrator email', 'owner@example.com')
            ->expectsQuestion('Password (minimum 12 characters)', 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->expectsOutputToContain('at least 12 characters')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
    }
}
