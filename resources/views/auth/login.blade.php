<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign in · BHC Brunei</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body>
<main class="login-page">
    <div class="card login-card border-0">
        <div class="row g-0">
            <section class="col-lg-6 login-story">
                <div class="page-eyebrow text-warning">Official operations system</div>
                <h1 class="display-5 mt-3" style="font-family:Georgia,serif">One record.<br>Every movement.</h1>
                <div class="official-rule"></div>
                <p class="text-white-50">A unified register for visa attestations, tokens, desk movement and Bangladesh worker applications in Brunei Darussalam.</p>
            </section>
            <section class="col-lg-6 login-form">
                <div class="page-eyebrow">Bangladesh High Commission</div>
                <h2 class="page-title">Welcome back</h2>
                <p class="text-secondary mb-4">Sign in with your authorized account.</p>
                @if($errors->any())
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>{{ $errors->first() }}</div>
                @endif
                <form method="post" action="{{ route('login.attempt') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email"><i class="bi bi-envelope me-2" aria-hidden="true"></i>Email address</label>
                        <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password"><i class="bi bi-lock me-2" aria-hidden="true"></i>Password</label>
                        <input class="form-control" id="password" name="password" type="password" required>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                        <label class="form-check-label" for="remember">Keep me signed in</label>
                    </div>
                    <button class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Sign in securely</button>
                </form>
                <p class="small text-secondary mt-4 mb-0"><i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Access attempts are recorded for security review.</p>
            </section>
        </div>
    </div>
</main>
</body>
</html>
