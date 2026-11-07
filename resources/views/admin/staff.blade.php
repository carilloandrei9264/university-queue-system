@extends('admin.layout')

@section('title', 'Office staff')

@section('content')
    @if (is_string($temporaryPassword))
        <section class="credential" role="alert">
            <h2>Temporary password for {{ $temporaryAccountName }}</h2>
            <p><code data-temporary-password>{{ $temporaryPassword }}</code></p>
            <p>This password is shown once. Share it securely. The staff member must change it when they sign in.</p>
        </section>
    @endif

    <section>
        <h2>Add staff account</h2>
        <p class="muted">A strong temporary password is generated automatically and shown once after creation. Give it to the staff member through a secure channel; they must change it at first sign-in.</p>
        <form method="POST" action="{{ route('admin.staff.store') }}">
            @csrf
            <label for="new-full-name">Full name</label>
            <input id="new-full-name" name="full_name" type="text" maxlength="255" required>
            <label for="new-email">Email</label>
            <input id="new-email" name="email" type="email" maxlength="255" required>
            <label for="new-staff-department">Assigned department</label>
            <select id="new-staff-department" name="department_id" required>
                <option value="">Choose a department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>
            <input name="is_active" type="hidden" value="1">
            <button type="submit">Add staff account</button>
        </form>
    </section>

    <section>
        <h2>Existing staff</h2>
        @forelse ($staffMembers as $staff)
            <div class="record">
                <form method="POST" action="{{ route('admin.staff.update', $staff) }}">
                    @csrf
                    @method('PATCH')
                    <label for="name-{{ $staff->id }}">Full name</label>
                    <input id="name-{{ $staff->id }}" name="full_name" type="text" value="{{ $staff->full_name }}" maxlength="255" required>
                    <label for="email-{{ $staff->id }}">Email</label>
                    <input id="email-{{ $staff->id }}" name="email" type="email" value="{{ $staff->email }}" maxlength="255" required>
                    <label for="department-{{ $staff->id }}">Assigned department</label>
                    <select id="department-{{ $staff->id }}" name="department_id" required>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected($department->id === $staff->department_id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                    <input name="is_active" type="hidden" value="0">
                    <label class="inline">
                        <input name="is_active" type="checkbox" value="1" @checked($staff->is_active)>
                        Active
                    </label>
                    <button type="submit">Save staff account</button>
                </form>
                <form method="POST" action="{{ route('admin.staff.password.reset', $staff) }}">
                    @csrf
                    <button type="submit">Issue new temporary password</button>
                </form>
            </div>
        @empty
            <p>No staff accounts have been added.</p>
        @endforelse
    </section>
@endsection
