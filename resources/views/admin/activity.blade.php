@extends('admin.layout')

@section('title', 'Activity log')

@section('content')
    <section>
        <p class="muted">Successful account, office, service, and queue changes. Passwords and other secrets are never included.</p>
        <form method="GET" action="{{ route('admin.activity.index') }}">
            <label for="activity-action">Action</label>
            <select id="activity-action" name="action">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
            <label for="activity-search">Search actor or target</label>
            <input id="activity-search" name="search" type="search" maxlength="255" value="{{ request('search') }}">
            <button type="submit">Filter</button>
            <a href="{{ route('admin.activity.index') }}">Clear</a>
        </form>
    </section>

    <section>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">When</th>
                        <th scope="col">Actor</th>
                        <th scope="col">Action</th>
                        <th scope="col">Target</th>
                        <th scope="col">Department</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $log->actor?->full_name ?? 'System' }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->target_type }}{{ $log->target_id === null ? '' : ' #'.$log->target_id }}</td>
                            <td>{{ $log->department?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No activity matches those filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </section>
@endsection
