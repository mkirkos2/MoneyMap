<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'dark') === 'dark']) data-appearance="{{ $appearance ?? 'dark' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Apply the saved theme before app styles load to minimize theme flash. --}}
        <script>
            (function() {
                const allowed = ['light', 'dark', 'system'];
                let appearance = '{{ $appearance ?? "dark" }}';

                try {
                    const storedAppearance = localStorage.getItem('appearance');
                    if (allowed.includes(storedAppearance)) {
                        appearance = storedAppearance;
                    }
                } catch {}

                if (!allowed.includes(appearance)) {
                    appearance = 'dark';
                }

                const isDark = appearance === 'dark' || (
                    appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches
                );

                document.documentElement.dataset.appearance = appearance;
                document.documentElement.classList.toggle('dark', isDark);
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html { background-color: #f4f7f5; }
            html.dark { background-color: #0b1119; }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
