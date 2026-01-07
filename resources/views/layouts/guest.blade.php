<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SaganPay') }} - Auth</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('/assets/images/saganpay-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .glass-overlay {
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
        }

        .form-card {
            background: white;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border-radius: 8px;
        }

        input,
        select,
        button {
            border-radius: 8px !important;
        }

        input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }
    </style>
</head>

<body class="antialiased">
    <div class="glass-overlay min-h-screen w-full flex items-center justify-center p-4 lg:p-8">
        <div class="form-card w-full max-w-md p-8 lg:p-10 transition-all duration-500 transform">
            {{ $slot }}
        </div>
    </div>
    @livewireScripts
</body>

</html>