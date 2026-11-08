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

    <details class="admin-create" @if ($errors->any()) open @endif>
        <summary><span>Add staff account</span><span class="summary-action">Open form</span></summary>
        <p class="muted">A strong temporary password is generated automatically and shown once after creation. Give it to the staff member through a secure channel; they must change it at first sign-in.</p>
        <form class="admin-form-grid" method="POST" action="{{ route('admin.staff.store') }}">
            @csrf
            <div>
                <label for="new-full-name">Full name</label>
                <input id="new-full-name" name="full_name" type="text" maxlength="255" value="{{ old('full_name') }}" required>
            </div>
            <div>
                <label for="new-email">Email</label>
                <input id="new-email" name="email" type="email" maxlength="255" value="{{ old('email') }}" required>
            </div>
            <div>
                <label for="new-staff-department">Assigned department</label>
                <select id="new-staff-department" name="department_id" required>
                    <option value="">Choose a department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="new-staff-window">Assigned service window</label>
                <select id="new-staff-window" name="window_id" required>
                    <option value="">Choose a window</option>
                    @foreach ($departments as $department)
                        <optgroup label="{{ $department->name }}">
                            @foreach ($department->windows as $window)
                                <option value="{{ $window->id }}" @disabled($window->staff_id !== null) @selected(old('window_id') == $window->id)>{{ $window->name }}{{ $window->staff ? ' — assigned' : '' }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <span class="muted">Only one active staff member can be assigned to each window.</span>
            </div>
            <input name="is_active" type="hidden" value="1">
            <button type="submit">Add staff account</button>
        </form>
    </details>

    <section class="admin-list">
        <div class="admin-list-heading">
            <div>
                <h2>Office staff</h2>
                <p class="muted">{{ $staffMembers->total() }} total</p>
            </div>
            <form class="admin-search" method="GET" action="{{ route('admin.staff.index') }}">
                <label class="visually-hidden" for="staff-search">Search staff by name, email or department</label>
                <input id="staff-search" name="search" type="search" value="{{ request('search') }}" maxlength="255" placeholder="Search office staff">
                <button type="submit">Search</button>
                @if (request()->filled('search'))
                    <a href="{{ route('admin.staff.index') }}">Clear</a>
                @endif
            </form>
        </div>
        <div class="admin-record-list">
            @forelse ($staffMembers as $staff)
                <details class="admin-record">
                    <summary class="admin-record-summary">
                        <span class="admin-record-name">{{ $staff->full_name }}</span>
                        <span class="admin-record-meta">{{ $staff->email }} · {{ $staff->department?->name ?? 'No department' }} · {{ $staff->serviceWindow?->name ?? 'No window assigned' }} · {{ $staff->is_active ? 'Active' : 'Inactive' }}</span>
                        <span class="summary-action">Edit</span>
                    </summary>
                    <form class="admin-form-grid" method="POST" action="{{ route('admin.staff.update', $staff) }}">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="name-{{ $staff->id }}">Full name</label>
                            <input id="name-{{ $staff->id }}" name="full_name" type="text" value="{{ $staff->full_name }}" maxlength="255" required>
                        </div>
                        <div>
                            <label for="email-{{ $staff->id }}">Email</label>
                            <input id="email-{{ $staff->id }}" name="email" type="email" value="{{ $staff->email }}" maxlength="255" required>
                        </div>
                        <div>
                            <label for="department-{{ $staff->id }}">Assigned department</label>
                            <select id="department-{{ $staff->id }}" name="department_id" required>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected($department->id === $staff->department_id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="window-{{ $staff->id }}">Assigned service window</label>
                            <select id="window-{{ $staff->id }}" name="window_id" required>
                                <option value="">Choose a window</option>
                                @foreach ($departments as $department)
                                    <optgroup label="{{ $department->name }}">
                                        @foreach ($department->windows as $window)
                                            <option value="{{ $window->id }}" @disabled($window->staff_id !== null && $window->staff_id !== $staff->id) @selected($window->id === $staff->serviceWindow?->id)>{{ $window->name }}{{ $window->staff && $window->staff_id !== $staff->id ? ' — assigned' : '' }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <input name="is_active" type="hidden" value="0">
                        <label class="inline">
                            <input name="is_active" type="checkbox" value="1" @checked($staff->is_active)>
                            Active
                        </label>
                        <button type="submit">Save staff account</button>
                    </form>
                    <div class="admin-record-actions">
                        <form method="POST" action="{{ route('admin.staff.password.reset', $staff) }}">
                            @csrf
                            <button class="secondary" type="submit">Issue new temporary password</button>
                        </form>
                    </div>
                </details>
            @empty
                <div class="empty-state">
                    <h2>{{ request()->filled('search') ? 'No matching staff' : 'No staff accounts yet' }}</h2>
                    <p>{{ request()->filled('search') ? 'Try another name, email, or department.' : 'Create an account above and assign it to an office.' }}</p>
                    @if (request()->filled('search'))
                        <a href="{{ route('admin.staff.index') }}">Clear search</a>
                    @endif
                </div>
            @endforelse
        </div>
        {{ $staffMembers->links() }}
    </section>
@endsection
