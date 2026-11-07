@extends('admin.layout')

@section('title', 'Departments')

@section('content')
    <section>
        <h2>Add department</h2>
        <form method="POST" action="{{ route('admin.departments.store') }}">
            @csrf
            <label for="new-name">Name</label>
            <input id="new-name" name="name" type="text" maxlength="255" required>
            <label for="new-prefix">Ticket prefix (2–4 uppercase letters)</label>
            <input id="new-prefix" name="prefix" type="text" minlength="2" maxlength="4" pattern="[A-Z]{2,4}" required>
            <input name="is_active" type="hidden" value="1">
            <button type="submit">Add department</button>
        </form>
    </section>

    <section>
        <h2>Existing departments</h2>
        @forelse ($departments as $department)
            <div class="record">
                <p class="muted">{{ $department->services_count }} services · {{ $department->staff_count }} staff</p>
                <form method="POST" action="{{ route('admin.departments.update', $department) }}">
                    @csrf
                    @method('PATCH')
                    <label for="name-{{ $department->id }}">Name</label>
                    <input id="name-{{ $department->id }}" name="name" type="text" value="{{ $department->name }}" maxlength="255" required>
                    <label for="prefix-{{ $department->id }}">Prefix</label>
                    <input id="prefix-{{ $department->id }}" name="prefix" type="text" value="{{ $department->prefix }}" minlength="2" maxlength="4" pattern="[A-Z]{2,4}" required>
                    <input name="is_active" type="hidden" value="0">
                    <label class="inline">
                        <input name="is_active" type="checkbox" value="1" @checked($department->is_active)>
                        Active
                    </label>
                    <button type="submit">Save department</button>
                </form>
            </div>
        @empty
            <p>No departments have been added.</p>
        @endforelse
    </section>
@endsection
