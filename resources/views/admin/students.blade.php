@extends('admin.layout')

@section('title', 'Students')

@section('content')
    @if (is_string($temporaryPassword))
        <section class="credential" role="alert">
            <h2>Temporary password for {{ $temporaryAccountName }}</h2>
            <p><code data-temporary-password>{{ $temporaryPassword }}</code></p>
            <p>This password is shown once. Share it securely. The student must change it when they sign in.</p>
        </section>
    @endif

    <details class="admin-create" @if ($errors->has('full_name') || $errors->has('email') || $errors->has('student_id') || $errors->has('course')) open @endif>
        <summary><span>Add student account</span><span class="summary-action">Open form</span></summary>
        <p class="muted">A strong temporary password is generated automatically and shown once after creation. Give it to the student through a secure channel; they must change it at first sign-in.</p>
        <form class="admin-form-grid" method="POST" action="{{ route('admin.students.store') }}">
            @csrf
            <div>
                <label for="new-full-name">Full name</label>
                <input id="new-full-name" name="full_name" type="text" maxlength="255" value="{{ old('full_name') }}" required>
            </div>
            <div>
                <label for="new-email">University email</label>
                <input id="new-email" name="email" type="email" maxlength="255" value="{{ old('email') }}" required>
            </div>
            <div>
                <label for="new-student-id">Student ID</label>
                <input id="new-student-id" name="student_id" type="text" maxlength="255" value="{{ old('student_id') }}" required>
            </div>
            <div>
                <label for="new-course">Course</label>
                <input id="new-course" name="course" type="text" maxlength="255" value="{{ old('course') }}">
            </div>
            <input name="is_active" type="hidden" value="1">
            <button type="submit">Create student account</button>
        </form>
    </details>

    <details class="admin-create" @if (is_array($rosterPreview) || $errors->has('roster')) open @endif>
        <summary><span>Import or export student roster</span><span class="summary-action">CSV tools</span></summary>
        <p class="muted">Use a UTF-8 CSV with <code>student_id</code>, <code>full_name</code>, and <code>email</code>; <code>course</code> is optional. Imports create new accounts only. Existing or duplicate records are flagged, never overwritten. Keep student IDs as text in spreadsheet software so leading zeros are preserved.</p>
        <p class="muted">Imported accounts get a random, undisclosed temporary password. Issue a temporary password from each student record and share it through an approved secure channel. CSV exports never include credentials.</p>
        <div class="admin-record-actions">
            <a href="{{ route('admin.students.template') }}">Download CSV template</a>
            <a href="{{ route('admin.students.export') }}">Export student roster</a>
        </div>
        <form class="admin-form-grid" method="POST" action="{{ route('admin.students.import.preview') }}" enctype="multipart/form-data">
            @csrf
            <div>
                <label for="student-roster-file">CSV roster (maximum 5 MB and {{ \App\Services\StudentRosterCsv::MAX_ROWS }} student rows)</label>
                <input id="student-roster-file" name="roster" type="file" accept=".csv,text/csv" required>
            </div>
            <button type="submit" data-loading-text="Checking CSV…">Preview CSV</button>
        </form>
    </details>

    @if (is_array($rosterPreview))
        <section class="admin-list" aria-labelledby="roster-preview-heading">
            <div class="admin-list-heading">
                <div>
                    <h2 id="roster-preview-heading">Review CSV before importing</h2>
                    <p class="muted">
                        {{ $rosterPreview['counts']['ready'] }} ready ·
                        {{ $rosterPreview['counts']['duplicate'] }} duplicate ·
                        {{ $rosterPreview['counts']['invalid'] }} invalid
                    </p>
                </div>
            </div>
            <p class="muted">Only rows marked Ready will be created. Duplicate and invalid rows will be skipped. This preview expires after 15 minutes.</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">CSV row</th>
                            <th scope="col">Student ID</th>
                            <th scope="col">Full name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Course</th>
                            <th scope="col">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rosterPreview['rows'] as $row)
                            <tr>
                                <td>{{ $row['line'] }}</td>
                                <td>{{ $row['values']['student_id'] ?? '—' }}</td>
                                <td>{{ $row['values']['full_name'] ?? '—' }}</td>
                                <td>{{ $row['values']['email'] ?? '—' }}</td>
                                <td>{{ $row['values']['course'] ?? '—' }}</td>
                                <td>
                                    <span class="status-badge {{ ['ready' => 'completed', 'duplicate' => 'skipped', 'invalid' => 'cancelled'][$row['status']] }}">{{ ucfirst($row['status']) }}</span>
                                    @if ($row['messages'] !== [])
                                        <ul>
                                            @foreach ($row['messages'] as $message)
                                                <li>{{ $message }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="admin-record-actions">
                @if ($rosterPreview['counts']['ready'] > 0)
                    <form method="POST" action="{{ route('admin.students.import.confirm') }}">
                        @csrf
                        <button type="submit" data-loading-text="Importing students…">Import {{ $rosterPreview['counts']['ready'] }} ready students</button>
                    </form>
                @else
                    <p>No new records are ready to import. Fix the CSV and preview it again.</p>
                @endif
                <form method="POST" action="{{ route('admin.students.import.cancel') }}">
                    @csrf
                    <button class="secondary" type="submit">Discard preview</button>
                </form>
            </div>
        </section>
    @endif

    <section class="admin-list">
        <div class="admin-list-heading">
            <div>
                <h2>Students</h2>
                <p class="muted">{{ $students->total() }} total</p>
            </div>
            <form class="admin-search" method="GET" action="{{ route('admin.students.index') }}">
                <label class="visually-hidden" for="student-search">Search students by name, email, or student ID</label>
                <input id="student-search" name="search" type="search" value="{{ request('search') }}" maxlength="255" placeholder="Search students">
                <button type="submit">Search</button>
                @if (request()->filled('search'))
                    <a href="{{ route('admin.students.index') }}">Clear</a>
                @endif
            </form>
        </div>
        <div class="admin-record-list">
            @forelse ($students as $student)
                <details class="admin-record">
                    <summary class="admin-record-summary">
                        <span class="admin-record-name">{{ $student->full_name }}</span>
                        <span class="admin-record-meta">{{ $student->email }} · {{ $student->student_id }} · {{ $student->is_active ? 'Active' : 'Inactive' }}</span>
                        <span class="summary-action">Edit</span>
                    </summary>
                    <form class="admin-form-grid" method="POST" action="{{ route('admin.students.update', $student) }}">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="name-{{ $student->id }}">Full name</label>
                            <input id="name-{{ $student->id }}" name="full_name" type="text" value="{{ $student->full_name }}" maxlength="255" required>
                        </div>
                        <div>
                            <label for="email-{{ $student->id }}">University email</label>
                            <input id="email-{{ $student->id }}" name="email" type="email" value="{{ $student->email }}" maxlength="255" required>
                        </div>
                        <div>
                            <label for="student-id-{{ $student->id }}">Student ID</label>
                            <input id="student-id-{{ $student->id }}" name="student_id" type="text" value="{{ $student->student_id }}" maxlength="255" required>
                        </div>
                        <div>
                            <label for="course-{{ $student->id }}">Course</label>
                            <input id="course-{{ $student->id }}" name="course" type="text" value="{{ $student->course }}" maxlength="255">
                        </div>
                        <input name="is_active" type="hidden" value="0">
                        <label class="inline">
                            <input name="is_active" type="checkbox" value="1" @checked($student->is_active)>
                            Active
                        </label>
                        <button type="submit">Save student record</button>
                    </form>
                    <div class="admin-record-actions">
                        <form method="POST" action="{{ route('admin.students.password.reset', $student) }}">
                            @csrf
                            <button class="secondary" type="submit">Issue new temporary password</button>
                        </form>
                    </div>
                </details>
            @empty
                <div class="empty-state" role="status">
                    <h2>{{ request()->filled('search') ? 'No matching students' : 'No student records yet' }}</h2>
                    <p>{{ request()->filled('search') ? 'Try a different name, email, or student ID.' : 'Create a student account using the form above to get started.' }}</p>
                    @if (request()->filled('search'))
                        <a href="{{ route('admin.students.index') }}">Clear search</a>
                    @endif
                </div>
            @endforelse
        </div>
        {{ $students->links() }}
    </section>
@endsection
