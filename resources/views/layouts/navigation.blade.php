<nav id="navbar" class="fixed w-full z-50 transition-all duration-300 ease-in-out bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <!-- Logo -->
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center">
                        <img src="{{ asset('images/logo.png') }}" alt="Kweneng International Logo" class="h-10 w-auto">
                        <span class="ml-3 text-xl font-bold text-gray-800 hidden md:block">
                            Kweneng International
                        </span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden space-x-8 ml-10 sm:flex items-center">

                    <a href="{{ route('home') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('home') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                        Home
                    </a>

                    <a href="{{ route('about') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('about') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                        About
                    </a>

                    <a href="{{ route('academics') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('academics') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                        Academics
                    </a>

                    <a href="{{ route('admissions') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admissions') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                        Admissions
                    </a>

                    <a href="{{ route('faq') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('faq') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                        FAQ
                    </a>

                    <a href="{{ route('contact') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('contact') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                        Contact
                    </a>

                    <a href="{{ route('alumni') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('alumni') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                        Alumni
                    </a>

                </div>
            </div>

            <!-- Auth Section -->
            <div class="hidden sm:flex sm:items-center">

                @auth

                    @php
                        $dashboardRoute = match (Auth::user()->role) {
                            'admin' => 'admin.dashboard',
                            'teacher' => 'teacher.dashboard',
                            'headmaster' => 'headmaster.dashboard',
                            'librarian' => 'librarian.dashboard',
                            'accounts_officer' => 'accounts-officer.dashboard',
                            'student' => 'student.dashboard',
                            'parent' => 'parent.dashboard',
                            default => null,
                        };
                    @endphp

                    <div class="flex items-center">
                        <div class="text-right mr-3">

                            <div class="text-sm font-medium text-gray-800">
                                {{ Auth::user()->name }}
                            </div>

                            <div class="text-xs text-gray-500">

                                @if ($dashboardRoute && Route::has($dashboardRoute))
                                    <a href="{{ route($dashboardRoute) }}" class="hover:text-gray-700 hover:underline">
                                        {{ ucwords(str_replace('_', ' ', Auth::user()->role)) }} Dashboard
                                    </a>
                                @else
                                    {{ ucwords(str_replace('_', ' ', Auth::user()->role)) }}
                                @endif

                            </div>

                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="text-sm text-gray-700 hover:text-white hover:bg-red-600 px-3 py-1 rounded transition">
                                Log Out
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex items-center space-x-4">

                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-indigo-600 font-medium">
                            Login
                        </a>

                        <a href="{{ route('admissions') }}"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                            Apply Now
                        </a>

                    </div>

                @endauth

            </div>

            <!-- Mobile Menu Button -->
            <div class="flex items-center sm:hidden">

                <button id="mobile-menu-button"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">

                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>

                </button>

            </div>

        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden sm:hidden">

        <div class="pt-2 pb-3 space-y-1">

            <a href="{{ route('home') }}" class="block pl-3 pr-4 py-2 text-base font-medium">Home</a>
            <a href="{{ route('about') }}" class="block pl-3 pr-4 py-2 text-base font-medium">About</a>
            <a href="{{ route('academics') }}" class="block pl-3 pr-4 py-2 text-base font-medium">Academics</a>
            <a href="{{ route('admissions') }}" class="block pl-3 pr-4 py-2 text-base font-medium">Admissions</a>
            <a href="{{ route('faq') }}" class="block pl-3 pr-4 py-2 text-base font-medium">FAQ</a>
            <a href="{{ route('contact') }}" class="block pl-3 pr-4 py-2 text-base font-medium">Contact</a>
            <a href="{{ route('alumni') }}" class="block pl-3 pr-4 py-2 text-base font-medium">Alumni</a>

        </div>

        <!-- Mobile Auth -->
        <div class="pt-4 pb-1 border-t border-gray-200">

            @auth

                <div class="px-4">

                    <div class="font-medium text-base text-gray-800">
                        {{ Auth::user()->name }}
                    </div>

                    <div class="text-sm text-gray-500">

                        @if ($dashboardRoute && Route::has($dashboardRoute))
                            <a href="{{ route($dashboardRoute) }}" class="hover:text-gray-700 hover:underline">
                                {{ ucwords(str_replace('_', ' ', Auth::user()->role)) }} Dashboard
                            </a>
                        @endif

                    </div>

                    <div class="text-sm text-gray-500 mt-1">
                        {{ Auth::user()->email }}
                    </div>

                </div>

                <div class="mt-3">

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit"
                            class="block w-full text-left px-4 py-2 text-base text-gray-500 hover:text-white hover:bg-red-600">
                            Log Out
                        </button>

                    </form>

                </div>
            @else
                <a href="{{ route('login') }}"
                    class="block px-4 py-2 text-base text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                    Login
                </a>

                <a href="{{ route('admissions') }}"
                    class="block px-4 py-2 text-base text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                    Apply Now
                </a>

            @endauth

        </div>

    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const navbar = document.getElementById('navbar');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileButton = document.getElementById('mobile-menu-button');

        let lastScrollTop = 0;

        window.addEventListener('scroll', function() {

            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop > 50) {
                navbar.classList.add('bg-white/90', 'backdrop-blur-sm', 'shadow-md');
                navbar.classList.remove('bg-white', 'shadow-lg');
            } else {
                navbar.classList.add('bg-white', 'shadow-lg');
                navbar.classList.remove('bg-white/90', 'backdrop-blur-sm', 'shadow-md');
            }

            if (scrollTop > lastScrollTop && scrollTop > 100) {
                navbar.style.transform = 'translateY(-100%)';
            } else {
                navbar.style.transform = 'translateY(0)';
            }

            lastScrollTop = scrollTop;

        });

        if (mobileButton) {
            mobileButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }

    });
</script>
