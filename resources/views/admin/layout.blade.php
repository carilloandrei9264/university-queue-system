<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="app-header admin-header">
        <div class="header-inner">
            <a class="brand" href="{{ route('admin.dashboard') }}"><img class="brand-icon" src="{{ asset('images/nu-bulldog-icon.webp') }}" alt=""> NU QUEUE-IT</a>
            @include('admin._profile-menu')
        </div>
    </header>
    <main class="page-shell admin-shell">
        <nav class="admin-nav" aria-label="Admin sections">
            <a href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>Overview</a>
            <a href="{{ route('admin.departments.index') }}" @if (request()->routeIs('admin.departments.*')) aria-current="page" @endif>Departments</a>
            <a href="{{ route('admin.services.index') }}" @if (request()->routeIs('admin.services.*')) aria-current="page" @endif>Services</a>
            <a href="{{ route('admin.staff.index') }}" @if (request()->routeIs('admin.staff.*')) aria-current="page" @endif>Office staff</a>
            <a href="{{ route('admin.students.index') }}" @if (request()->routeIs('admin.students.*')) aria-current="page" @endif>Students</a>
            <a href="{{ route('admin.activity.index') }}" @if (request()->routeIs('admin.activity.*')) aria-current="page" @endif>Activity</a>
        </nav>

        <div class="admin-content">
        @if (session('status'))
            <p class="status success-state" data-state="success" role="status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <section class="error validation-summary" role="alert" tabindex="-1" data-validation-summary>
                <p>Please review the following and try again:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <h1 class="admin-page-title">@yield('title')</h1>
        @yield('content')
        </div>
    </main>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
