<nav id="navbar" class="fixed top-0 w-full z-50 transition-all duration-300 ease-in-out">
    @php
        $isHomePage = request()->routeIs('home');
    @endphp

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
    @endauth

    @if ($isHomePage)
        <!-- Homepage Expanded Branding -->
        <div id="home-branding"
            class="hidden sm:block overflow-hidden transition-all duration-500 ease-in-out bg-gradient-to-r from-sky-50 via-slate-100 to-emerald-50 border-b border-slate-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                <div class="flex items-center justify-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-5">
                        <img src="{{ asset('images/logo.png') }}" alt="Kweneng International Logo"
                            class="h-24 w-auto drop-shadow-sm">
                        <div class="leading-tight">
                            <div class="text-4xl font-extrabold text-slate-900 tracking-tight">
                                Kweneng International
                            </div>
                            <div class="text-lg font-semibold text-sky-700">
                                Secondary School
                            </div>
                            <div class="text-sm text-slate-600 mt-1">
                                Cambridge Excellence • Honour First
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Nav -->
    <div id="main-nav-bar" class="bg-slate-500 border-b border-slate-600 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">

                <!-- Logo -->
                <div class="flex min-w-0">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('home') }}" class="flex items-center min-w-0">
                            <img src="{{ asset('images/logo.png') }}" alt="Kweneng International Logo"
                                class="h-10 w-auto sm:h-10">
                            <div class="ml-3 min-w-0">
                                <span class="block text-base sm:text-xl font-bold text-white leading-tight truncate">
                                    Kweneng International
                                </span>
                                <span class="block text-[11px] sm:hidden text-slate-100 leading-tight truncate">
                                    Secondary School
                                </span>
                            </div>
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden space-x-8 ml-10 sm:flex items-center">
                        <a href="{{ route('home') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('home') ? 'border-[#2baffc] text-white' : 'border-transparent text-slate-100 hover:text-white hover:border-slate-200' }} text-sm font-medium">
                            Home
                        </a>

                        <a href="{{ route('about') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('about') ? 'border-[#2baffc] text-white' : 'border-transparent text-slate-100 hover:text-white hover:border-slate-200' }} text-sm font-medium">
                            About
                        </a>

                        <a href="{{ route('academics') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('academics') ? 'border-[#2baffc] text-white' : 'border-transparent text-slate-100 hover:text-white hover:border-slate-200' }} text-sm font-medium">
                            Academics
                        </a>

                        <a href="{{ route('admissions') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('admissions') ? 'border-[#2baffc] text-white' : 'border-transparent text-slate-100 hover:text-white hover:border-slate-200' }} text-sm font-medium">
                            Admissions
                        </a>

                        <a href="{{ route('faq') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('faq') ? 'border-[#2baffc] text-white' : 'border-transparent text-slate-100 hover:text-white hover:border-slate-200' }} text-sm font-medium">
                            FAQ
                        </a>

                        <a href="{{ route('contact') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('contact') ? 'border-[#2baffc] text-white' : 'border-transparent text-slate-100 hover:text-white hover:border-slate-200' }} text-sm font-medium">
                            Contact
                        </a>

                        <a href="{{ route('alumni') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('alumni') ? 'border-[#2baffc] text-white' : 'border-transparent text-slate-100 hover:text-white hover:border-slate-200' }} text-sm font-medium">
                            Alumni
                        </a>
                    </div>
                </div>

                <!-- Auth Section -->
                <div class="hidden sm:flex sm:items-center">
                    @auth
                        <div class="relative" id="account-dropdown-wrapper">
                            <button id="account-dropdown-button"
                                class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white hover:text-white hover:bg-slate-600 transition">
                                <div class="text-right mr-2 leading-tight">
                                    <div class="font-medium text-white">
                                        {{ Auth::user()->name }}
                                    </div>
                                    <div class="text-xs text-slate-100">
                                        {{ ucwords(str_replace('_', ' ', Auth::user()->role)) }}
                                    </div>
                                </div>
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div id="account-dropdown-menu"
                                class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50">
                                @if ($dashboardRoute && Route::has($dashboardRoute))
                                    <a href="{{ route($dashboardRoute) }}"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-indigo-600">
                                        Dashboard
                                    </a>
                                @endif

                                <a href="{{ route('password.edit') }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-indigo-600">
                                    Change Password
                                </a>

                                <div class="border-t border-gray-100 my-1"></div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:text-white hover:bg-red-600">
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('login') }}" class="text-white hover:text-sky-100 font-medium">
                                Login
                            </a>

                            <a href="{{ route('admissions') }}"
                                class="bg-indigo-700 hover:bg-indigo-800 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                                Apply Now
                            </a>
                        </div>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center sm:hidden flex-shrink-0">
                    <button id="mobile-menu-button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-white hover:text-white hover:bg-slate-600">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path id="mobile-menu-icon-open" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path id="mobile-menu-icon-close" class="hidden" stroke-linecap="round"
                                stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Accent Branding Line -->
    <div class="h-[3px] w-full bg-gradient-to-r from-[#2baffc] to-[#55c360]"></div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden sm:hidden bg-slate-600 border-t border-slate-500 shadow-lg">
        <div class="pt-2 pb-3 space-y-1">
            <a href="{{ route('home') }}"
                class="block pl-4 pr-4 py-3 text-base font-medium {{ request()->routeIs('home') ? 'text-white bg-slate-700 border-l-4 border-[#2baffc]' : 'text-slate-100 hover:text-white hover:bg-slate-700' }}">
                Home
            </a>

            <a href="{{ route('about') }}"
                class="block pl-4 pr-4 py-3 text-base font-medium {{ request()->routeIs('about') ? 'text-white bg-slate-700 border-l-4 border-[#2baffc]' : 'text-slate-100 hover:text-white hover:bg-slate-700' }}">
                About
            </a>

            <a href="{{ route('academics') }}"
                class="block pl-4 pr-4 py-3 text-base font-medium {{ request()->routeIs('academics') ? 'text-white bg-slate-700 border-l-4 border-[#2baffc]' : 'text-slate-100 hover:text-white hover:bg-slate-700' }}">
                Academics
            </a>

            <a href="{{ route('admissions') }}"
                class="block pl-4 pr-4 py-3 text-base font-medium {{ request()->routeIs('admissions') ? 'text-white bg-slate-700 border-l-4 border-[#2baffc]' : 'text-slate-100 hover:text-white hover:bg-slate-700' }}">
                Admissions
            </a>

            <a href="{{ route('faq') }}"
                class="block pl-4 pr-4 py-3 text-base font-medium {{ request()->routeIs('faq') ? 'text-white bg-slate-700 border-l-4 border-[#2baffc]' : 'text-slate-100 hover:text-white hover:bg-slate-700' }}">
                FAQ
            </a>

            <a href="{{ route('contact') }}"
                class="block pl-4 pr-4 py-3 text-base font-medium {{ request()->routeIs('contact') ? 'text-white bg-slate-700 border-l-4 border-[#2baffc]' : 'text-slate-100 hover:text-white hover:bg-slate-700' }}">
                Contact
            </a>

            <a href="{{ route('alumni') }}"
                class="block pl-4 pr-4 py-3 text-base font-medium {{ request()->routeIs('alumni') ? 'text-white bg-slate-700 border-l-4 border-[#2baffc]' : 'text-slate-100 hover:text-white hover:bg-slate-700' }}">
                Alumni
            </a>
        </div>

        <div class="pt-4 pb-3 border-t border-slate-500">
            @auth
                <div class="px-4">
                    <div class="font-medium text-base text-white">
                        {{ Auth::user()->name }}
                    </div>

                    <div class="text-sm text-slate-100">
                        {{ ucwords(str_replace('_', ' ', Auth::user()->role)) }}
                    </div>

                    <div class="text-sm text-slate-100 mt-1">
                        {{ Auth::user()->email }}
                    </div>
                </div>

                <div class="mt-3">
                    @if ($dashboardRoute && Route::has($dashboardRoute))
                        <a href="{{ route($dashboardRoute) }}"
                            class="block w-full text-left px-4 py-3 text-base text-slate-100 hover:text-white hover:bg-slate-700">
                            Dashboard
                        </a>
                    @endif

                    <a href="{{ route('password.edit') }}"
                        class="block w-full text-left px-4 py-3 text-base text-slate-100 hover:text-white hover:bg-slate-700">
                        Change Password
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="block w-full text-left px-4 py-3 text-base text-slate-100 hover:text-white hover:bg-red-600">
                            Log Out
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}"
                    class="block px-4 py-3 text-base text-slate-100 hover:text-white hover:bg-slate-700">
                    Login
                </a>

                <a href="{{ route('admissions') }}"
                    class="block px-4 py-3 text-base text-slate-100 hover:text-white hover:bg-slate-700">
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
        const mobileMenuIconOpen = document.getElementById('mobile-menu-icon-open');
        const mobileMenuIconClose = document.getElementById('mobile-menu-icon-close');

        const accountDropdownButton = document.getElementById('account-dropdown-button');
        const accountDropdownMenu = document.getElementById('account-dropdown-menu');
        const accountDropdownWrapper = document.getElementById('account-dropdown-wrapper');

        const homeBranding = document.getElementById('home-branding');
        const navbarSpacer = document.getElementById('navbar-spacer');

        let lastScrollTop = 0;

        function isMobileView() {
            return window.innerWidth < 640;
        }

        function isHomePage() {
            return !!homeBranding;
        }

        function updateNavbarSpacer() {
            if (navbar && navbarSpacer) {
                navbarSpacer.style.height = navbar.offsetHeight + 'px';
            }
        }

        function resetNavbarForMobile() {
            navbar.style.transform = 'translateY(0)';

            if (homeBranding) {
                homeBranding.style.maxHeight = '';
                homeBranding.style.opacity = '';
                homeBranding.style.transform = '';
                homeBranding.style.paddingTop = '';
                homeBranding.style.paddingBottom = '';
            }

            updateNavbarSpacer();
        }

        function handleHomeBranding(scrollTop) {
            if (!isHomePage() || isMobileView()) return;

            if (scrollTop > 40) {
                homeBranding.style.maxHeight = '0px';
                homeBranding.style.opacity = '0';
                homeBranding.style.transform = 'translateY(-12px)';
                homeBranding.style.paddingTop = '0';
                homeBranding.style.paddingBottom = '0';
            } else {
                homeBranding.style.maxHeight = '180px';
                homeBranding.style.opacity = '1';
                homeBranding.style.transform = 'translateY(0)';
            }
        }

        function updateNavbarOnScroll() {
            if (isMobileView()) {
                resetNavbarForMobile();
                return;
            }

            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop > lastScrollTop && scrollTop > 100) {
                navbar.style.transform = 'translateY(-100%)';
            } else {
                navbar.style.transform = 'translateY(0)';
            }

            handleHomeBranding(scrollTop);
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;

            updateNavbarSpacer();
        }

        window.addEventListener('scroll', updateNavbarOnScroll);
        window.addEventListener('resize', function() {
            if (isMobileView()) {
                resetNavbarForMobile();
            } else {
                updateNavbarOnScroll();
            }
            updateNavbarSpacer();
        });

        window.addEventListener('load', updateNavbarSpacer);

        if (homeBranding) {
            homeBranding.style.maxHeight = '180px';
            homeBranding.style.opacity = '1';
            homeBranding.style.transform = 'translateY(0)';
        }

        updateNavbarOnScroll();
        updateNavbarSpacer();

        if (mobileButton) {
            mobileButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');

                if (mobileMenuIconOpen && mobileMenuIconClose) {
                    mobileMenuIconOpen.classList.toggle('hidden');
                    mobileMenuIconClose.classList.toggle('hidden');
                }

                navbar.style.transform = 'translateY(0)';
                updateNavbarSpacer();
            });
        }

        if (accountDropdownButton && accountDropdownMenu) {
            accountDropdownButton.addEventListener('click', function(e) {
                e.stopPropagation();
                accountDropdownMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', function(e) {
                if (accountDropdownWrapper && !accountDropdownWrapper.contains(e.target)) {
                    accountDropdownMenu.classList.add('hidden');
                }
            });
        }
    });
</script>
