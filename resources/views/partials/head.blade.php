<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta http-equiv="X-UA-Compatible" content="ie=edge" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $appName }}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-flags.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-themes.min.css" />

<!-- CSS color accent-->
<style>
    .footer {
        padding: 1rem 0;
    }

    .navbar.bg-primary {
        --tblr-navbar-bg: var(--tblr-primary);
        --tblr-navbar-color: var(--tblr-on-primary, #fff);
    }
</style>
