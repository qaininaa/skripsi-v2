<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div
        x-data="{ sidebarOpen: false }"
        @keydown.escape.window="sidebarOpen = false"
        :class="sidebarOpen ? 'overflow-hidden lg:overflow-visible' : ''"
        class="flex min-h-screen overflow-hidden"
    >
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        ></div>

        <aside
            id="app-sidebar"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 transform transition-transform duration-300 ease-in-out lg:static lg:z-auto lg:translate-x-0 lg:shrink-0"
            :aria-hidden="sidebarOpen ? 'false' : 'true'"
        >
            @include('components.sidebar.sidebar')
        </aside>

        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            @include('components.layouts.header')

            <main class="flex-1 overflow-y-auto bg-gray-100 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>

        <x-modals.delete-modal />
    </div>

    @stack('scripts')
</body>
</html>
