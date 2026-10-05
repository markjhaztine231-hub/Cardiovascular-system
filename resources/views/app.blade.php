<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Cardiovascular Care Center') }}</title>


        <!-- Scripts -->
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-[#f4f6f9]">
        <!--
          The @inertia directive is the magic bridge!
          It tells Laravel to mount our Vue 3 app exactly here.
        -->
        @inertia
    </body>
</html>
