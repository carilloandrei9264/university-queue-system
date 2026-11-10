<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StudentRosterCsvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
    }

    public function test_admin_can_download_template_and_export_only_roster_fields(): void
    {
        User::create([
            'full_name' => '=HYPERLINK("https://example.com")',
            'email' => 'formula.student@example.com',
            'student_id' => '00042',
            'course' => 'BS Information Technology',
            'password' => 'temporary-password',
            'role' => 'student',
            'is_active' => true,
        ]);

        $template = $this->get(route('admin.students.template'))->assertOk();
        $this->assertStringContainsString(
            'student_id,full_name,email,course',
            $template->streamedContent(),
        );

        $response = $this->get(route('admin.students.export'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
        $csv = $response->streamedContent();

        $this->assertStringContainsString('student_id,full_name,email,course', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('00042', $csv);
        $this->assertStringNotContainsString('temporary-password', $csv);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => auth()->id(),
            'action' => 'student.roster_exported',
            'target_type' => 'student_roster',
            'target_id' => null,
        ]);
    }

    public function test_import_preview_marks_valid_duplicate_and_invalid_rows_without_creating_accounts(): void
    {
        User::where('student_id', 'DEMO-2026-001')->update(['email' => 'STUDENT001@EXAMPLE.COM']);
        $csv = implode("\r\n", [
            'student_id,full_name,email,course',
            '000101,New Student,new.student@example.com,BS IT',
            'DEMO-2026-001,Existing Student,existing@example.com,BS IT',
            '000102,Invalid Email,not-an-email,BS IT',
            '000101,Repeated Student,repeated@example.com,BS IT',
            '000103,Email Duplicate,STUDENT001@EXAMPLE.COM,BS IT',
        ]);

        $this->post(route('admin.students.import.preview'), [
            'roster' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])->assertRedirect(route('admin.students.index'));

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('1 ready')
            ->assertSee('3 duplicate')
            ->assertSee('1 invalid')
            ->assertSee('000101')
            ->assertSee('not-an-email')
            ->assertSee('Import 1 ready students');

        $this->assertDatabaseMissing('users', ['email' => 'new.student@example.com']);
    }

    public function test_confirm_import_creates_only_ready_records_with_secure_temporary_passwords(): void
    {
        $csv = implode("\r\n", [
            'student_id,full_name,email,course',
            '000201,Imported Student,imported.student@example.com,BS Accountancy',
            'DEMO-2026-001,Existing Student,existing@example.com,',
            '000202,Invalid Email,invalid,',
        ]);

        $this->post(route('admin.students.import.preview'), [
            'roster' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])->assertRedirect(route('admin.students.index'));

        $this->post(route('admin.students.import.confirm'))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHas('status', fn (string $status): bool => str_contains($status, '1 student accounts were imported'))
            ->assertSessionMissing('student_roster_preview_token');

        $student = User::where('email', 'imported.student@example.com')->firstOrFail();
        $this->assertSame('000201', $student->student_id);
        $this->assertSame('BS Accountancy', $student->course);
        $this->assertSame('student', $student->role);
        $this->assertTrue($student->is_active);
        $this->assertTrue($student->must_change_password);
        $this->assertNotSame('temporary-password', $student->password);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.created',
            'target_id' => $student->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.roster_imported',
            'target_type' => 'student_roster',
            'target_id' => null,
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'existing@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'invalid']);
    }

    public function test_preview_confirmation_refuses_records_that_became_duplicates(): void
    {
        $csv = implode("\r\n", [
            'student_id,full_name,email',
            '000301,Pending Student,pending.student@example.com',
        ]);

        $this->post(route('admin.students.import.preview'), [
            'roster' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ]);

        User::create([
            'full_name' => 'Concurrent Student',
            'email' => 'concurrent@example.com',
            'student_id' => '000301',
            'password' => 'temporary-password',
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->post(route('admin.students.import.confirm'))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors('roster');

        $this->assertDatabaseMissing('users', ['email' => 'pending.student@example.com']);
    }

    public function test_preview_rejects_unknown_headers_and_files_over_the_row_limit(): void
    {
        $this->post(route('admin.students.import.preview'), [
            'roster' => UploadedFile::fake()->createWithContent(
                'valid.csv',
                "student_id,full_name,email\n000500,Valid Student,valid.student@example.com",
            ),
        ]);

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.import.preview'), [
                'roster' => UploadedFile::fake()->createWithContent(
                    'students.csv',
                    "student_number,name,email\n000501,Wrong Headers,wrong.headers@example.com",
                ),
            ])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors('roster')
            ->assertSessionMissing('student_roster_preview_token');

        $lines = ['student_id,full_name,email'];
        foreach (range(1, 501) as $number) {
            $lines[] = sprintf('%06d,Student %d,student%d@example.com', $number, $number, $number);
        }

        $this->from(route('admin.students.index'))
            ->post(route('admin.students.import.preview'), [
                'roster' => UploadedFile::fake()->createWithContent('large-roster.csv', implode("\r\n", $lines)),
            ])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors('roster');

        $this->assertDatabaseMissing('users', ['email' => 'student1@example.com']);
    }

    public function test_expired_preview_cannot_be_imported(): void
    {
        $token = 'expired-preview-token';
        Cache::store('file')->put('student-roster-preview:'.auth()->id().':'.$token, [
            'created_at' => now()->subMinutes(16)->timestamp,
            'rows' => [[
                'status' => 'ready',
                'values' => [
                    'student_id' => '000601',
                    'full_name' => 'Expired Student',
                    'email' => 'expired.student@example.com',
                    'course' => '',
                ],
            ]],
        ], now()->addMinutes(15));

        $this->withSession([
            'student_roster_preview_token' => $token,
        ])->post(route('admin.students.import.confirm'))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors('roster')
            ->assertSessionMissing('student_roster_preview_token');

        $this->assertDatabaseMissing('users', ['email' => 'expired.student@example.com']);
    }

    public function test_student_cannot_use_roster_import_or_export_routes(): void
    {
        $student = User::where('role', 'student')->firstOrFail();
        $this->actingAs($student)
            ->get(route('admin.students.export'))
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('admin.students.import.preview'), [
                'roster' => UploadedFile::fake()->createWithContent(
                    'students.csv',
                    "student_id,full_name,email\n000401,Unauthorized Student,unauthorized@example.com",
                ),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.com']);
    }
}
