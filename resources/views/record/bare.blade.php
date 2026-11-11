<!doctype html>
{{-- The Record inside the phone app (see RECORD.md): the same page without
     Firefly's header and sidebar. The app opens it from api/v1/record/app with
     its API token, in the app's own light or dark. --}}
<html lang="en" data-record-app="1" data-bs-theme="{{ $theme }}" style="color-scheme: {{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <base href="{{ route('index', null, true) }}/">
    <title>Record</title>
    @vite(['sass/app.scss'])
</head>
<body class="bg-body-tertiary">
<main class="container-fluid py-2">
    @yield('content')
</main>
@yield('scripts')
</body>
</html>
