<!doctype html>
<html lang="en" data-bs-theme-primary="{{ auth()->user()->theme ?? config('app.theme.primary') }}">
<head>
    @include('partials.head')
</head>
<body class="layout-fluid navbar-collapsed">
<div class="page">
    @include('partials.sidebar')
    @include('partials.navbar')

    <div class="page-wrapper">
        <!-- BEGIN PAGE HEADER -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <!-- Page pre-title -->
                        <div class="page-pretitle">{{ $pretitle }}</div>
                        <h2 class="page-title">{{ $title }}</h2>
                    </div>
                    <!-- Page title actions -->
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            {{ $actions ?? '' }}
                        </div>
                        <!-- BEGIN MODAL -->
                        <!-- END MODAL -->
                    </div>
                </div>
            </div>
        </div>
        <!-- END PAGE HEADER -->
        <!-- BEGIN PAGE BODY -->
        <div class="page-body">
            <div class="container-xl">
                {{ $slot }}
            </div>
        </div>
        <!-- END PAGE BODY -->
        @include('partials.footer')
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>
</body>
</html>
