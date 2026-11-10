<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StudentRosterCsv
{
    public const MAX_ROWS = 500;

    private const REQUIRED_HEADERS = ['student_id', 'full_name', 'email'];

    private const ALLOWED_HEADERS = ['student_id', 'full_name', 'email', 'course'];

    public function preview(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $handle = is_string($path) ? fopen($path, 'rb') : false;

        if ($handle === false) {
            throw ValidationException::withMessages([
                'roster' => 'The uploaded CSV could not be read. Please choose another file.',
            ]);
        }

        try {
            $headers = fgetcsv($handle, null, ',', '"', '');

            if ($headers === false) {
                throw ValidationException::withMessages([
                    'roster' => 'The CSV file is empty. Download the template and add student records.',
                ]);
            }

            if (! mb_check_encoding(implode('', array_map('strval', $headers)), 'UTF-8')) {
                throw ValidationException::withMessages([
                    'roster' => 'Save the CSV as UTF-8 and upload it again.',
                ]);
            }

            $headers = array_map(
                static fn (mixed $header): string => mb_strtolower(trim((string) $header)),
                $headers,
            );
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];

            if (count($headers) !== count(array_unique($headers)) ||
                array_diff(self::REQUIRED_HEADERS, $headers) !== [] ||
                array_diff($headers, self::ALLOWED_HEADERS) !== []) {
                throw ValidationException::withMessages([
                    'roster' => 'CSV headers must include student_id, full_name, email, and optionally course. Use the provided template.',
                ]);
            }

            $candidates = [];
            $lineNumber = 1;

            while (($fields = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $lineNumber++;

                if ($fields === [null] || collect($fields)->every(static fn ($value): bool => trim((string) $value) === '')) {
                    continue;
                }

                if (count($candidates) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages([
                        'roster' => 'A CSV can contain at most '.self::MAX_ROWS.' student rows. Split the roster into smaller files.',
                    ]);
                }

                if (count($fields) !== count($headers)) {
                    $candidates[] = $this->rowResult($lineNumber, [], 'invalid', ['The number of fields does not match the CSV header.']);

                    continue;
                }

                if (! mb_check_encoding(implode('', array_map('strval', $fields)), 'UTF-8')) {
                    $candidates[] = $this->rowResult($lineNumber, [], 'invalid', ['Save this row as UTF-8 and upload the CSV again.']);

                    continue;
                }

                $values = array_combine($headers, array_map(
                    static fn (mixed $value): string => trim((string) $value),
                    $fields,
                ));
                $values['student_id'] = trim($values['student_id']);
                $values['full_name'] = trim($values['full_name']);
                $values['email'] = mb_strtolower(trim($values['email']));
                $values['course'] = trim($values['course'] ?? '');

                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', implode('', $values)) === 1) {
                    $candidates[] = $this->rowResult($lineNumber, $values, 'invalid', ['Fields contain unsupported control characters.']);

                    continue;
                }

                $validator = Validator::make($values, [
                    'student_id' => ['required', 'string', 'max:255'],
                    'full_name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255'],
                    'course' => ['nullable', 'string', 'max:255'],
                ]);

                $candidates[] = $this->rowResult(
                    $lineNumber,
                    $values,
                    $validator->fails() ? 'invalid' : 'ready',
                    $validator->errors()->all(),
                );
            }
        } finally {
            fclose($handle);
        }

        $validCandidates = array_filter($candidates, static fn (array $row): bool => $row['status'] === 'ready');
        $studentIds = array_column(array_column($validCandidates, 'values'), 'student_id');
        $emails = array_column(array_column($validCandidates, 'values'), 'email');
        $existing = User::query()
            ->where(function ($query) use ($studentIds, $emails): void {
                $query->whereIn('student_id', array_unique($studentIds))
                    ->orWhereIn(DB::raw('LOWER(email)'), array_unique($emails));
            })
            ->get(['student_id', 'email']);
        $existingIds = array_fill_keys($existing->pluck('student_id')->all(), true);
        $existingEmails = array_fill_keys($existing->map(static fn (User $user): string => mb_strtolower($user->email))->all(), true);
        $seenIds = [];
        $seenEmails = [];

        foreach ($candidates as &$row) {
            if ($row['status'] !== 'ready') {
                continue;
            }

            $studentId = $row['values']['student_id'];
            $email = $row['values']['email'];
            $messages = [];

            if (isset($existingIds[$studentId]) || isset($seenIds[$studentId])) {
                $messages[] = 'Student ID already exists in the system or this file.';
            }

            if (isset($existingEmails[$email]) || isset($seenEmails[$email])) {
                $messages[] = 'Email already exists in the system or this file.';
            }

            if ($messages !== []) {
                $row['status'] = 'duplicate';
                $row['messages'] = $messages;

                continue;
            }

            $seenIds[$studentId] = true;
            $seenEmails[$email] = true;
        }
        unset($row);

        return [
            'rows' => array_values($candidates),
            'counts' => [
                'ready' => count(array_filter($candidates, static fn (array $row): bool => $row['status'] === 'ready')),
                'duplicate' => count(array_filter($candidates, static fn (array $row): bool => $row['status'] === 'duplicate')),
                'invalid' => count(array_filter($candidates, static fn (array $row): bool => $row['status'] === 'invalid')),
            ],
        ];
    }

    public function import(array $rows, User $actor, ActivityLogger $activityLogger): int
    {
        return DB::transaction(function () use ($rows, $actor, $activityLogger): int {
            $studentIds = array_column($rows, 'student_id');
            $emails = array_column($rows, 'email');
            $hasConflicts = User::query()
                ->where(function ($query) use ($studentIds, $emails): void {
                    $query->whereIn('student_id', $studentIds)
                        ->orWhereIn(DB::raw('LOWER(email)'), $emails);
                })
                ->exists();

            if ($hasConflicts) {
                throw ValidationException::withMessages([
                    'roster' => 'The student roster changed after preview. Please upload the CSV again to review the latest records.',
                ]);
            }

            foreach ($rows as $row) {
                $student = User::create([
                    ...$row,
                    'role' => 'student',
                    'password' => bin2hex(random_bytes(24)),
                    'is_active' => true,
                    'must_change_password' => true,
                ]);
                $activityLogger->record($actor, 'student.created', 'student', $student->id);
            }

            $activityLogger->record($actor, 'student.roster_imported', 'student_roster', null);

            return count($rows);
        }, attempts: 3);
    }

    public function exportRows(): iterable
    {
        return User::query()
            ->where('role', 'student')
            ->orderBy('student_id')
            ->cursor();
    }

    public function safeExportValue(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) === 1
            ? "'".$value
            : $value;
    }

    private function rowResult(int $lineNumber, array $values, string $status, array $messages): array
    {
        return [
            'line' => $lineNumber,
            'values' => $values,
            'status' => $status,
            'messages' => $messages,
        ];
    }
}
