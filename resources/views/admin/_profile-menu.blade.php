<details class="profile-menu">
    <summary class="profile-trigger">
        <span class="profile-avatar" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->full_name, 0, 1)) }}</span>
        <span class="profile-copy">
            <strong>{{ auth()->user()->full_name }}</strong>
            <span>Administration</span>
        </span>
        <span class="profile-chevron" aria-hidden="true"></span>
    </summary>
    <div class="profile-dropdown">
        <p>Administrator account<br><strong>{{ auth()->user()->full_name }}</strong></p>
        <form method="POST" action="{{ route('logout') }}" data-logout-form>
            @csrf
            <button class="profile-logout" type="submit" data-loading-text="Signing out…">Sign out</button>
        </form>
    </div>
</details>
