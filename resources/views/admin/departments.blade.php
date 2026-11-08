@extends('admin.layout')

@section('title', 'Departments')

@section('content')
    <details class="admin-create" @if ($errors->any()) open @endif>
        <summary><span>Add department</span><span class="summary-action">Open form</span></summary>
        <form class="admin-form-grid" method="POST" action="{{ route('admin.departments.store') }}">
            @csrf
            <div>
                <label for="new-name">Name</label>
                <input id="new-name" name="name" type="text" maxlength="255" value="{{ old('name') }}" required>
            </div>
            <div>
                <label for="new-prefix">Ticket prefix (2–4 uppercase letters)</label>
                <input id="new-prefix" name="prefix" type="text" minlength="2" maxlength="4" pattern="[A-Z]{2,4}" value="{{ old('prefix') }}" required>
            </div>
            <div>
                <label for="new-window-count">Number of service windows</label>
                <input id="new-window-count" name="window_count" type="number" min="1" max="20" value="{{ old('window_count', 3) }}" required>
            </div>
            <input name="is_active" type="hidden" value="1">
            <button type="submit">Add department</button>
        </form>
    </details>

    <section class="admin-list">
        <div class="admin-list-heading">
            <div>
                <h2>Departments</h2>
                <p class="muted">{{ $departments->total() }} total</p>
            </div>
            <form class="admin-search" method="GET" action="{{ route('admin.departments.index') }}">
                <label class="visually-hidden" for="department-search">Search departments by name or prefix</label>
                <input id="department-search" name="search" type="search" value="{{ request('search') }}" maxlength="255" placeholder="Search departments">
                <button type="submit">Search</button>
                @if (request()->filled('search'))
                    <a href="{{ route('admin.departments.index') }}">Clear</a>
                @endif
            </form>
        </div>
        <div class="admin-record-list">
            @forelse ($departments as $department)
                <details class="admin-record">
                    <summary class="admin-record-summary">
                        <span class="admin-record-name">{{ $department->name }} <span class="prefix">{{ $department->prefix }}</span></span>
                        <span class="admin-record-meta">{{ $department->services_count }} services · {{ $department->staff_count }} staff · {{ $department->active_windows_count }} windows · {{ $department->is_active ? 'Active' : 'Inactive' }}</span>
                        <span class="summary-action">Edit</span>
                    </summary>
                    <form class="admin-form-grid" method="POST" action="{{ route('admin.departments.update', $department) }}">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="name-{{ $department->id }}">Name</label>
                            <input id="name-{{ $department->id }}" name="name" type="text" value="{{ $department->name }}" maxlength="255" required>
                        </div>
                        <div>
                            <label for="prefix-{{ $department->id }}">Prefix</label>
                            <input id="prefix-{{ $department->id }}" name="prefix" type="text" value="{{ $department->prefix }}" minlength="2" maxlength="4" pattern="[A-Z]{2,4}" required>
                        </div>
                        <div>
                            <label for="windows-{{ $department->id }}">Number of service windows</label>
                            <input id="windows-{{ $department->id }}" name="window_count" type="number" min="1" max="20" value="{{ $department->active_windows_count }}" required>
                        </div>
                        <input name="is_active" type="hidden" value="0">
                        <label class="inline">
                            <input name="is_active" type="checkbox" value="1" @checked($department->is_active)>
                            Active
                        </label>
                        <button type="submit">Save department</button>
                    </form>
                </details>
            @empty
                <div class="empty-state">
                    <h2>{{ request()->filled('search') ? 'No matching departments' : 'No departments yet' }}</h2>
                    <p>{{ request()->filled('search') ? 'Try another name or prefix.' : 'Add a department using the form above to set up an office queue.' }}</p>
                    @if (request()->filled('search'))
                        <a href="{{ route('admin.departments.index') }}">Clear search</a>
                    @endif
                </div>
            @endforelse
        </div>
        {{ $departments->links() }}
    </section>
@endsection
