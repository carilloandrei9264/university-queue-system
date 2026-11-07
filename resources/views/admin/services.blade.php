@extends('admin.layout')

@section('title', 'Services')

@section('content')
    <details class="admin-create" @if ($errors->any()) open @endif>
        <summary><span>Add service</span><span class="summary-action">Open form</span></summary>
        <form class="admin-form-grid" method="POST" action="{{ route('admin.services.store') }}">
            @csrf
            <div>
                <label for="new-department">Department</label>
                <select id="new-department" name="department_id" required>
                    <option value="">Choose a department</option>
                    @foreach ($departments->where('is_active', true) as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="new-service">Service name</label>
                <input id="new-service" name="name" type="text" maxlength="255" value="{{ old('name') }}" required>
            </div>
            <input name="is_active" type="hidden" value="1">
            <button type="submit">Add service</button>
        </form>
    </details>

    <section class="admin-list">
        <div class="admin-list-heading">
            <div>
                <h2>Services</h2>
                <p class="muted">{{ $services->total() }} total</p>
            </div>
            <form class="admin-search" method="GET" action="{{ route('admin.services.index') }}">
                <label class="visually-hidden" for="service-search">Search services or departments</label>
                <input id="service-search" name="search" type="search" value="{{ request('search') }}" maxlength="255" placeholder="Search services">
                <button type="submit">Search</button>
                @if (request()->filled('search'))
                    <a href="{{ route('admin.services.index') }}">Clear</a>
                @endif
            </form>
        </div>
        <div class="admin-record-list">
            @forelse ($services as $service)
                <details class="admin-record">
                    <summary class="admin-record-summary">
                        <span class="admin-record-name">{{ $service->name }}</span>
                        <span class="admin-record-meta">{{ $service->department->name }} · {{ $service->is_active ? 'Active' : 'Inactive' }}</span>
                        <span class="summary-action">Edit</span>
                    </summary>
                    <form class="admin-form-grid" method="POST" action="{{ route('admin.services.update', $service) }}">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="department-{{ $service->id }}">Department</label>
                            <select id="department-{{ $service->id }}" name="department_id" required>
                                @foreach ($departments as $department)
                                    @if ($department->is_active || $department->id === $service->department_id)
                                        <option value="{{ $department->id }}" @selected($department->id === $service->department_id)>
                                            {{ $department->name }}{{ $department->is_active ? '' : ' (inactive)' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="service-{{ $service->id }}">Service name</label>
                            <input id="service-{{ $service->id }}" name="name" type="text" value="{{ $service->name }}" maxlength="255" required>
                        </div>
                        <input name="is_active" type="hidden" value="0">
                        <label class="inline">
                            <input name="is_active" type="checkbox" value="1" @checked($service->is_active)>
                            Active
                        </label>
                        <button type="submit">Save service</button>
                    </form>
                </details>
            @empty
                <div class="empty-state">
                    <h2>{{ request()->filled('search') ? 'No matching services' : 'No services yet' }}</h2>
                    <p>{{ request()->filled('search') ? 'Try another service or department name.' : 'Add a service above so students can join an office queue.' }}</p>
                    @if (request()->filled('search'))
                        <a href="{{ route('admin.services.index') }}">Clear search</a>
                    @endif
                </div>
            @endforelse
        </div>
        {{ $services->links() }}
    </section>
@endsection
