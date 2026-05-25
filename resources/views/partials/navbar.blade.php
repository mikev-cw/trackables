<header class="navbar navbar-expand-md sticky-top d-none d-print-none d-lg-flex bg-primary">
    <div class="container-xl">
        <!-- BEGIN NAVBAR TOGGLER -->
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbar-menu"
            aria-controls="navbar-menu"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- END NAVBAR TOGGLER -->
        <!-- BEGIN NAVBAR LOGO -->
        <div class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
        </div>
        <!-- END NAVBAR LOGO -->
        <div class="navbar-nav flex-row order-md-last">
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 p-0 px-2" data-bs-toggle="dropdown" aria-label="Open user menu">
                    <div class="d-none d-xl-block ps-2">
                        <div>{{ $userName }}</div>
                        <div class="mt-1 small text-secondary"></div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="#" class="dropdown-item">
                        Settings
                        <span class="badge badge-sm bg-yellow-lt ms-auto">Soon</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="{{route('logout')}}" class="dropdown-item">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>
