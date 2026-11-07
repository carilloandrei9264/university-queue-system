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

    <section>
        <h2>Add student account</h2>
        <p class="muted">A strong temporary password is generated automatically and shown once after creation. Give it to the student through a secure channel; they must change it at first sign-in.</p>
        <form method="POST" action="{{ route('admin.students.store') }}">
            @csrf
            <label for="new-full-name">Full name</label>
            <input id="new-full-name" name="full_name" type="text" maxlength="255" required>
            <label for="new-email">University email</label>
            <input id="new-email" name="email" type="email" maxlength="255" required>
            <label for="new-student-id">Student ID</label>
            <input id="new-student-id" name="student_id" type="text" maxlength="255" required>
            <label for="new-course">Course</label>
            <input id="new-course" name="course" type="text" maxlength="255">
            <input name="is_active" type="hidden" value="1">
            <button type="submit">Create student account</button>
        </form>
    </section>

    <section>
        <h2>Existing student records</h2>
        <form method="GET" action="{{ route('admin.students.index') }}">
            <label for="student-search">Search by name, email, or student ID</label>
            <input id="student-search" name="search" type="search" value="{{ request('search') }}" maxlength="255">
            <button type="submit">Search</button>
        </form>
        @forelse ($students as $student)
            <div class="record">
                <form method="POST" action="{{ route('admin.students.update', $student) }}">
                    @csrf
                    @method('PATCH')
                    <label for="name-{{ $student->id }}">Full name</label>
                    <input id="name-{{ $student->id }}" name="full_name" type="text" value="{{ $student->full_name }}" maxlength="255" required>
                    <label for="email-{{ $student->id }}">University email</label>
                    <input id="email-{{ $student->id }}" name="email" type="email" value="{{ $student->email }}" maxlength="255" required>
                    <label for="student-id-{{ $student->id }}">Student ID</label>
                    <input id="student-id-{{ $student->id }}" name="student_id" type="text" value="{{ $student->student_id }}" maxlength="255" required>
                    <label for="course-{{ $student->id }}">Course</label>
                    <input id="course-{{ $student->id }}" name="course" type="text" value="{{ $student->course }}" maxlength="255">
                    <input name="is_active" type="hidden" value="0">
                    <label class="inline">
                        <input name="is_active" type="checkbox" value="1" @checked($student->is_active)>
                        Active
                    </label>
                    <button type="submit">Save student record</button>
                </form>
                <form method="POST" action="{{ route('admin.students.password.reset', $student) }}">
                    @csrf
                    <button type="submit">Issue new temporary password</button>
                </form>
            </div>
        @empty
            <p>No student records have been added.</p>
        @endforelse
        {{ $students->links() }}
    </section>
@endsection
