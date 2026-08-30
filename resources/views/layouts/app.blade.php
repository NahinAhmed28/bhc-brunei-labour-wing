<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Registry') · BHC Brunei</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/filters.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/token-modal.css') }}">
    {{-- Flatpickr date picker (same library as boesel-visa) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @stack('styles')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="primary-sidebar">
        <div class="brand">
            <div class="brand-mark">BD</div>
            <div class="brand-copy">
                <div class="brand-kicker">Labour Wing</div>
                <div class="brand-title">Bangladesh High Commission</div>
                <div class="brand-sub">Brunei Darussalam</div>
            </div>
        </div>
        <nav class="sidebar-navigation" aria-label="Primary navigation">
            <section class="nav-sector" aria-labelledby="operations-sector">
                <div class="nav-sector-heading" id="operations-sector">
                    <span class="nav-sector-icon"><i class="bi bi-grid-1x2" aria-hidden="true"></i></span>
                    <span><strong>Operations</strong><small>Daily workflow</small></span>
                </div>
                <div class="nav-sector-links">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="nav-link-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
                        <span class="nav-link-copy"><strong>Dashboard</strong><small>Mission overview</small></span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('tokens.*') ? 'active' : '' }}" href="{{ route('tokens.index') }}">
                        <span class="nav-link-icon"><i class="bi bi-ticket-detailed" aria-hidden="true"></i></span>
                        <span class="nav-link-copy"><strong>Tokens</strong><small>Submission registry</small></span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('workers.*') ? 'active' : '' }}" href="{{ route('workers.index') }}">
                        <span class="nav-link-icon"><i class="bi bi-passport" aria-hidden="true"></i></span>
                        <span class="nav-link-copy"><strong>Workers</strong><small>Movement records</small></span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                        <span class="nav-link-icon"><i class="bi bi-bar-chart-line" aria-hidden="true"></i></span>
                        <span class="nav-link-copy"><strong>Reports</strong><small>Operational exports</small></span>
                    </a>
                </div>
            </section>

            @if(auth()->user()->hasAnyRole('super-admin', 'administrator', 'viewer'))
            <section class="nav-sector" aria-labelledby="management-sector">
                <div class="nav-sector-heading" id="management-sector">
                    <span class="nav-sector-icon"><i class="bi bi-gear-wide-connected" aria-hidden="true"></i></span>
                    <span><strong>Management</strong><small>Records and access</small></span>
                </div>
                <div class="nav-sector-links">
                @if(auth()->user()->hasAnyRole('super-admin', 'administrator'))
                    <a class="nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}">
                        <span class="nav-link-icon"><i class="bi bi-buildings" aria-hidden="true"></i></span>
                        <span class="nav-link-copy"><strong>Companies</strong><small>Employer directory</small></span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('agencies.*') ? 'active' : '' }}" href="{{ route('agencies.index') }}">
                        <span class="nav-link-icon"><i class="bi bi-briefcase" aria-hidden="true"></i></span>
                        <span class="nav-link-copy"><strong>Agencies</strong><small>Recruiter directory</small></span>
                    </a>
                @endif
                @if(auth()->user()->isSuperAdmin())
                    <a class="nav-link {{ request()->routeIs('configuration') ? 'active' : '' }}" href="{{ route('configuration') }}">
                        <span class="nav-link-icon"><i class="bi bi-sliders" aria-hidden="true"></i></span>
                        <span class="nav-link-copy"><strong>Configuration</strong><small>System controls</small></span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                        <span class="nav-link-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                        <span class="nav-link-copy"><strong>Users</strong><small>Access management</small></span>
                    </a>
                @endif
                @if(auth()->user()->hasAnyRole('super-admin', 'viewer'))
                    <a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}">
                        <span class="nav-link-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                        <span class="nav-link-copy"><strong>Audit trail</strong><small>Recorded activity</small></span>
                    </a>
                @endif
                </div>
            </section>
            @endif
        </nav>
        <div class="sidebar-foot">
            <span class="sidebar-foot-seal"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
            <span><strong>Official registry</strong><small>Secure mission records</small></span>
        </div>
    </aside>
    <button class="sidebar-backdrop" type="button" data-sidebar-dismiss aria-label="Close navigation"></button>
    <main class="main">
        <header class="topbar">
            <button class="btn sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle navigation" aria-controls="primary-sidebar" aria-expanded="true">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <div class="d-none d-lg-block text-secondary small">Visa & worker operations registry</div>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <span class="me-2 rounded-circle bg-success-subtle text-success p-2"><i class="bi bi-person" aria-hidden="true"></i></span>{{ auth()->user()->name }}
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <div class="px-3 py-2 small text-secondary">{{ auth()->user()->role?->label }}</div>
                    <div class="dropdown-divider"></div>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign out</button>
                    </form>
                </div>
            </div>
        </header>
        <div class="content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2" aria-hidden="true"></i>{{ session('success') }}
                    <button class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <strong><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Please review the highlighted fields.</strong>
                    <ul class="mb-0 mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
<div class="modal fade" id="tokenDetailsModal" tabindex="-1" aria-labelledby="tokenDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" data-token-modal-content>
            <div class="modal-header"><h2 class="modal-title" id="tokenDetailsModalLabel">Token details</h2></div>
            <div class="modal-body text-center py-5"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading token details</span></div></div>
        </div>
    </div>
</div>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>

{{-- Flatpickr: auto-initialise all date / datetime inputs (same pattern as boesel-visa) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof flatpickr === 'undefined') return;

    document.querySelectorAll("input[type='date']").forEach(function (el) {
        if (el._flatpickr) return;
        flatpickr(el, {
            altInput: true,
            altFormat: 'd M Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
            clickOpens: true
        });
    });

    document.querySelectorAll("input[type='datetime-local']").forEach(function (el) {
        if (el._flatpickr) return;
        flatpickr(el, {
            enableTime: true,
            time_24hr: true,
            altInput: true,
            altFormat: 'd M Y H:i',
            dateFormat: 'Y-m-d H:i',
            allowInput: true,
            clickOpens: true
        });
    });
});
</script>

{{-- Normalise typographic hyphens to ASCII on inputs that opt in --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-normalize-hyphen]').forEach(function (input) {
        var normalize = function () {
            var s = input.selectionStart, e = input.selectionEnd;
            var v = input.value.replace(/[‐‑‒–—−]/g, '-');
            if (input.value !== v) {
                input.value = v;
                if (typeof input.setSelectionRange === 'function') input.setSelectionRange(s, e);
            }
        };
        input.addEventListener('keydown', function (ev) {
            if (ev.key === '-' || ev.key === 'Subtract') ev.stopPropagation();
        });
        input.addEventListener('input', normalize);
        input.addEventListener('paste', function () { setTimeout(normalize, 0); });
    });
});
</script>

@stack('scripts')
</body>
</html>
