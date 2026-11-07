@extends('admin.layout')

@section('title', 'Activity log')

@section('content')
    <section>
        <p class="muted">Successful account, office, service, and queue changes. Passwords and other secrets are never included.</p>
        <form class="activity-filter-form" method="GET" action="{{ route('admin.activity.index') }}">
            <div class="activity-filter-field">
                <label for="activity-action">Action</label>
                <select id="activity-action" name="action">
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="activity-filter-search">
                <label for="activity-search">Search actor or target</label>
                <div class="activity-search-controls">
                    <input id="activity-search" name="search" type="search" maxlength="255" value="{{ request('search') }}">
                    <button type="submit">Filter</button>
                    <a class="button secondary activity-clear" href="{{ route('admin.activity.index') }}">Clear</a>
                </div>
            </div>
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
                        <tr>
                            <td colspan="5">
                                @if (request()->filled('search') || request()->filled('action'))
                                    <div class="empty-state">
                                        <h2>No matching activity</h2>
                                        <p>Try a different search or action filter.</p>
                                        <a href="{{ route('admin.activity.index') }}">Clear filters</a>
                                    </div>
                                @else
                                    <div class="empty-state">
                                        <h2>No activity recorded yet</h2>
                                        <p>Successful account, office, service, and queue changes will appear here.</p>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </section>
@endsection
