@auth
    @php
        $user = auth()->user();
    @endphp

    <header class="shrink-0 border-b border-gray-200 bg-white shadow-sm">
        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="sidebarOpen = !sidebarOpen"
                    aria-controls="app-sidebar"
                    :aria-expanded="sidebarOpen ? 'true' : 'false'"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-800 lg:hidden"
                >
                    <span class="sr-only">Buka menu navigasi</span>
                    <img src="{{ asset('icons/burger.svg') }}" alt="" class="h-5 w-5" aria-hidden="true">
                </button>
                <h1 class="text-lg font-semibold text-gray-800">
                    @hasSection('page-title')
                        @yield('page-title')
                    @else
                        Dashboard
                    @endif
                </h1>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden items-center gap-3 sm:flex">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold leading-tight text-gray-800">{{ $user->name }}</p>
                        <p class="text-xs capitalize leading-tight text-gray-500">{{ $user->role }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600 cursor-pointer"
                    >
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </header>
@endauth
