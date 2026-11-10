<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\StudentRosterCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): View|Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($validated['search'] ?? '');

        $temporaryPassword = $request->session()->pull('temporary_password');
        $previewToken = $request->session()->get('student_roster_preview_token');
        $rosterPreview = is_string($previewToken)
            ? Cache::store('file')->get($this->rosterPreviewCacheKey($request, $previewToken))
            : null;

        if (! is_array($rosterPreview) || ($rosterPreview['created_at'] ?? 0) < now()->subMinutes(15)->timestamp) {
            $this->forgetRosterPreview($request);
            $rosterPreview = null;
        }

        $response = response()->view('admin.students', [
            'students' => User::query()
                ->where('role', 'student')
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
                    });
                })
                ->orderBy('full_name')
                ->paginate(15)
                ->withQueryString(),
            'temporaryPassword' => $temporaryPassword,
            'temporaryAccountName' => $request->session()->pull('temporary_account_name'),
            'rosterPreview' => $rosterPreview,
        ]);

        return is_string($temporaryPassword) || is_array($rosterPreview)
            ? $response->header('Cache-Control', 'no-store, private')
            : $response;
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['student_id', 'full_name', 'email', 'course'], ',', '"', '');
            fclose($output);
        }, 'student-roster-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function previewImport(Request $request, StudentRosterCsv $roster): RedirectResponse
    {
        $this->forgetRosterPreview($request);
        $request->validate([
            'roster' => ['required', 'file', 'extensions:csv', 'max:5120'],
        ]);

        $preview = $roster->preview($request->file('roster'));
        $previewToken = Str::uuid()->toString();
        Cache::store('file')->put($this->rosterPreviewCacheKey($request, $previewToken), [
            ...$preview,
            'created_at' => now()->timestamp,
        ], now()->addMinutes(15));
        $request->session()->put('student_roster_preview_token', $previewToken);

        return redirect()->route('admin.students.index')
            ->with('status', 'CSV preview ready. Review the rows before importing.');
    }

    public function confirmImport(Request $request, StudentRosterCsv $roster, ActivityLogger $activityLogger): RedirectResponse
    {
        $previewToken = $request->session()->pull('student_roster_preview_token');
        $preview = is_string($previewToken)
            ? Cache::store('file')->pull($this->rosterPreviewCacheKey($request, $previewToken))
            : null;

        if (! is_array($preview) || ($preview['created_at'] ?? 0) < now()->subMinutes(15)->timestamp) {
            return redirect()->route('admin.students.index')
                ->withErrors(['roster' => 'The CSV preview expired. Upload the roster again to review it.']);
        }

        $rows = collect($preview['rows'] ?? [])
            ->where('status', 'ready')
            ->map(static fn (array $row): array => [
                'student_id' => $row['values']['student_id'],
                'full_name' => $row['values']['full_name'],
                'email' => $row['values']['email'],
                'course' => $row['values']['course'] ?: null,
            ])
            ->values()
            ->all();

        if ($rows === []) {
            return redirect()->route('admin.students.index')
                ->with('status', 'No new student records were imported. Fix the CSV errors and upload it again.');
        }

        try {
            $importedCount = $roster->import($rows, $request->user(), $activityLogger);
        } catch (ValidationException $exception) {
            return redirect()->route('admin.students.index')
                ->withErrors($exception->errors());
        }

        return redirect()->route('admin.students.index')
            ->with('status', "{$importedCount} student accounts were imported. Passwords were not included or displayed; issue temporary passwords individually before sharing account access.");
    }

    public function cancelImport(Request $request): RedirectResponse
    {
        $this->forgetRosterPreview($request);

        return redirect()->route('admin.students.index')->with('status', 'CSV preview discarded.');
    }

    public function export(Request $request, StudentRosterCsv $roster, ActivityLogger $activityLogger): StreamedResponse
    {
        $activityLogger->record($request->user(), 'student.roster_exported', 'student_roster', null);

        return response()->streamDownload(function () use ($roster): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['student_id', 'full_name', 'email', 'course'], ',', '"', '');

            foreach ($roster->exportRows() as $student) {
                fputcsv($output, [
                    $roster->safeExportValue($student->student_id),
                    $roster->safeExportValue($student->full_name),
                    $roster->safeExportValue($student->email),
                    $roster->safeExportValue($student->course),
                ], ',', '"', '');
            }

            fclose($output);
        }, 'student-roster-'.now(config('app.timezone'))->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);
        $attributes['role'] = 'student';
        $temporaryPassword = Str::password(24, symbols: false);
        $attributes['password'] = $temporaryPassword;
        $attributes['must_change_password'] = true;

        $student = DB::transaction(function () use ($attributes, $request, $activityLogger): User {
            $student = User::create($attributes);
            $activityLogger->record($request->user(), 'student.created', 'student', $student->id);

            return $student;
        });

        return redirect()->route('admin.students.index')
            ->with('status', "Student account for {$student->full_name} was created.")
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_account_name', $student->full_name);
    }

    public function update(Request $request, User $student, ActivityLogger $activityLogger): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        DB::transaction(function () use ($request, $student, $activityLogger): void {
            $student->update($this->validatedAttributes($request, $student));
            $activityLogger->record($request->user(), 'student.updated', 'student', $student->id);
        });

        return redirect()->route('admin.students.index')
            ->with('status', "Student record for {$student->full_name} was updated.");
    }

    public function resetPassword(Request $request, User $student, ActivityLogger $activityLogger): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);
        $temporaryPassword = Str::password(24, symbols: false);

        DB::transaction(function () use ($request, $student, $activityLogger, $temporaryPassword): void {
            $student->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
            ])->save();
            $activityLogger->record($request->user(), 'student.temporary_password_reset', 'student', $student->id);
        });

        return redirect()->route('admin.students.index')
            ->with('status', "A new temporary password was issued for {$student->full_name}.")
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_account_name', $student->full_name);
    }

    private function validatedAttributes(Request $request, ?User $student = null): array
    {
        $email = $request->input('email');
        $studentId = $request->input('student_id');

        if (is_string($email)) {
            $request->merge(['email' => mb_strtolower(trim($email))]);
        }

        if (is_string($studentId)) {
            $request->merge(['student_id' => trim($studentId)]);
        }

        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student?->id),
            ],
            'student_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'student_id')->ignore($student?->id),
            ],
            'course' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function forgetRosterPreview(Request $request): void
    {
        $previewToken = $request->session()->pull('student_roster_preview_token');

        if (is_string($previewToken)) {
            Cache::store('file')->forget($this->rosterPreviewCacheKey($request, $previewToken));
        }
    }

    private function rosterPreviewCacheKey(Request $request, string $token): string
    {
        return "student-roster-preview:{$request->user()->id}:{$token}";
    }
}
