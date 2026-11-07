@extends('admin.layout')

@section('title', 'Services')

@section('content')
    <section>
        <h2>Add service</h2>
        <form method="POST" action="{{ route('admin.services.store') }}">
            @csrf
            <label for="new-department">Department</label>
            <select id="new-department" name="department_id" required>
                <option value="">Choose a department</option>
                @foreach ($departments->where('is_active', true) as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            <label for="new-service">Service name</label>
            <input id="new-service" name="name" type="text" maxlength="255" value="{{ old('name') }}" required>
            <input name="is_active" type="hidden" value="1">
            <button type="submit">Add service</button>
        </form>
    </section>

    <section>
        <h2>Existing services</h2>
        @forelse ($services as $service)
            <div class="record">
                <form method="POST" action="{{ route('admin.services.update', $service) }}">
                    @csrf
                    @method('PATCH')
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
                    <label for="service-{{ $service->id }}">Service name</label>
                    <input id="service-{{ $service->id }}" name="name" type="text" value="{{ $service->name }}" maxlength="255" required>
                    <input name="is_active" type="hidden" value="0">
                    <label class="inline">
                        <input name="is_active" type="checkbox" value="1" @checked($service->is_active)>
                        Active
                    </label>
                    <button type="submit">Save service</button>
                </form>
            </div>
        @empty
            <div class="empty-state">
                <h2>No services yet</h2>
                <p>Add a service above so students can join an office queue.</p>
            </div>
        @endforelse
    </section>
@endsection
