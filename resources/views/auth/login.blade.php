
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.1.1/dist/css/tabler.min.css" />
</head>
<body>
<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <!-- BEGIN NAVBAR LOGO -->
            <a href="." class="navbar-brand navbar-brand-autodark">
                <img src="{{asset('storage/images/trackables_logo.png')}}" width="145px" alt="{{ config('app.name') }}">
            </a>
            <!-- END NAVBAR LOGO -->
        </div>
        <div class="card card-md">
            <div class="card-body">
                <div id="errorBox" class="error"></div>
                <form  id="loginForm" autocomplete="off" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Email address</label>
                        <input id="email" type="email" class="form-control" placeholder="your@email.com" autocomplete="off" />
                    </div>
                    <div class="mb-2">
                        <label class="form-label">
                            Password
                        </label>
                        <div class="input-group input-group-flat">
                            <input id="password" type="password" class="form-control" placeholder="Your password" autocomplete="off" />
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">Sign in</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    const form = document.getElementById('loginForm');
    const err = document.getElementById('errorBox');
    function showError(msg){ err.textContent = msg; err.style.display = 'block'; }

    form.addEventListener('submit', async e => {
        e.preventDefault();
        err.style.display = 'none';

        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        try {
            const res = await fetch('{{ route('login.post', absolute: false) }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    email,
                    password: password
                })
            });

            const data = await res.json();

            if (!res.ok || !data.ok) {
                showError(data.error || 'Login failed.');
                return;
            }

            window.location.href = data.redirect;
        } catch (err) {
            showError('Network error.');
        }
    });
</script>
</body>
</html>
