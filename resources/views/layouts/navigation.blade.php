<nav id="navbar" class="fixed w-full z-50 transition-all duration-300 ease-in-out bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
    <a href="{{ route('home') }}" class="flex items-center">
        <img src="{{ asset('images/logo.png') }}" alt="Kweneng International Logo" class="h-10 w-auto">
        <span class="ml-3 text-xl font-bold text-gray-800 hidden md:block">Kweneng International</span>
    </a>
</div>


                <!-- Desktop Navigation Links -->
                <div class="hidden space-x-8 ml-10 sm:-my-px sm:ms-10 sm:flex items-center">
                    <a href="{{ route('home') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{
                           request()->routeIs('home') 
                               ? 'border-indigo-400 text-gray-900' 
                               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                       }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        {{ __('Home') }}
                    </a>
                    <a href="{{ route('about') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{
                           request()->routeIs('about') || request()->routeIs('pages.about')
                               ? 'border-indigo-400 text-gray-900' 
                               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                       }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        {{ __('About') }}
                    </a>
                    <a href="{{ route('academics') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{
                           request()->routeIs('academics') || request()->routeIs('pages.academics')
                               ? 'border-indigo-400 text-gray-900' 
                               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                       }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        {{ __('Academics') }}
                    </a>
                    <a href="{{ route('admissions') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{
                           request()->routeIs('admissions') || request()->routeIs('pages.admissions')
                               ? 'border-indigo-400 text-gray-900' 
                               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                       }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        {{ __('Admissions') }}
                    </a>
                    <a href="{{ route('faq') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{
                           request()->routeIs('faq') || request()->routeIs('pages.faq')
                               ? 'border-indigo-400 text-gray-900' 
                               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                       }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        {{ __('FAQ') }}
                    </a>
                    <a href="{{ route('contact') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{
                           request()->routeIs('contact') || request()->routeIs('pages.contact')
                               ? 'border-indigo-400 text-gray-900' 
                               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                       }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        {{ __('Contact') }}
                    </a>
                    <a href="{{ route('alumni') }}" 
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{
                           request()->routeIs('alumni') || request()->routeIs('pages.alumni')
                               ? 'border-indigo-400 text-gray-900' 
                               : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                       }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        {{ __('Alumni') }}
                    </a>
                </div>
            </div>

            <!-- Auth/Apply Section -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <div class="ml-3 relative">
                        <div class="flex items-center">
                            <div class="text-right mr-3">
                                <div class="text-sm font-medium text-gray-800">
                                    {{ Auth::user()->name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    <a href="{{ route(Auth::user()->role . '.dashboard') }}" 
                                       class="hover:text-gray-700 hover:underline">
                                        {{ ucfirst(Auth::user()->role) }} Dashboard
                                    </a>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" 
                                        class="text-sm text-gray-700 hover:text-white hover:bg-red-600 px-3 py-1 rounded transition duration-150 ease-in-out">
                                    {{ __('Log Out') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-indigo-600 font-medium">
                            {{ __('Login') }}
                        </a>
                        <a href="{{ route('admissions') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-300">
                            Apply Now
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center sm:hidden">
                <button id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div id="mobile-menu" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <a href="{{ route('home') }}" 
               class="block pl-3 pr-4 py-2 border-l-4 {{
                   request()->routeIs('home') 
                       ? 'border-indigo-400 text-indigo-700 bg-indigo-50' 
                       : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300'
               }} text-base font-medium transition duration-150 ease-in-out">
                {{ __('Home') }}
            </a>
            <a href="{{ route('about') }}" 
               class="block pl-3 pr-4 py-2 border-l-4 {{
                   request()->routeIs('about') || request()->routeIs('pages.about')
                       ? 'border-indigo-400 text-indigo-700 bg-indigo-50' 
                       : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300'
               }} text-base font-medium transition duration-150 ease-in-out">
                {{ __('About') }}
            </a>
            <a href="{{ route('academics') }}" 
               class="block pl-3 pr-4 py-2 border-l-4 {{
                   request()->routeIs('academics') || request()->routeIs('pages.academics')
                       ? 'border-indigo-400 text-indigo-700 bg-indigo-50' 
                       : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300'
               }} text-base font-medium transition duration-150 ease-in-out">
                {{ __('Academics') }}
            </a>
            <a href="{{ route('admissions') }}" 
               class="block pl-3 pr-4 py-2 border-l-4 {{
                   request()->routeIs('admissions') || request()->routeIs('pages.admissions')
                       ? 'border-indigo-400 text-indigo-700 bg-indigo-50' 
                       : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300'
               }} text-base font-medium transition duration-150 ease-in-out">
                {{ __('Admissions') }}
            </a>
            <a href="{{ route('faq') }}" 
               class="block pl-3 pr-4 py-2 border-l-4 {{
                   request()->routeIs('faq') || request()->routeIs('pages.faq')
                       ? 'border-indigo-400 text-indigo-700 bg-indigo-50' 
                       : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300'
               }} text-base font-medium transition duration-150 ease-in-out">
                {{ __('FAQ') }}
            </a>
            <a href="{{ route('contact') }}" 
               class="block pl-3 pr-4 py-2 border-l-4 {{
                   request()->routeIs('contact') || request()->routeIs('pages.contact')
                       ? 'border-indigo-400 text-indigo-700 bg-indigo-50' 
                       : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300'
               }} text-base font-medium transition duration-150 ease-in-out">
                {{ __('Contact') }}
            </a>
            <a href="{{ route('alumni') }}" 
               class="block pl-3 pr-4 py-2 border-l-4 {{
                   request()->routeIs('alumni') || request()->routeIs('pages.alumni')
                       ? 'border-indigo-400 text-indigo-700 bg-indigo-50' 
                       : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300'
               }} text-base font-medium transition duration-150 ease-in-out">
                {{ __('Alumni') }}
            </a>
        </div>

        <!-- Mobile Auth Section -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            @auth
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">
                        {{ Auth::user()->name }}
                    </div>
                    <div class="font-medium text-sm text-gray-500">
                        <a href="{{ route(Auth::user()->role . '.dashboard') }}" 
                           class="hover:text-gray-700 hover:underline">
                            {{ ucfirst(Auth::user()->role) }} Dashboard
                        </a>
                    </div>
                    <div class="font-medium text-sm text-gray-500 mt-1">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-base font-medium text-gray-500 hover:text-white hover:bg-red-600 focus:outline-none focus:bg-red-600 transition duration-150 ease-in-out">
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </div>
            @else
                <div class="mt-3 space-y-1">
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                        {{ __('Login') }}
                    </a>
                    <a href="{{ route('admissions') }}" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                        {{ __('Apply Now') }}
                    </a>
                </div>
            @endauth
        </div>
    </div>
</nav>

<!-- Navbar scroll behavior script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.getElementById('navbar');
    let lastScrollTop = 0;
    
    // Add scroll event listener
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Change navbar appearance on scroll
        if (scrollTop > 50) {
            navbar.classList.add('bg-white/90', 'backdrop-blur-sm', 'shadow-md');
            navbar.classList.remove('bg-white', 'shadow-lg');
        } else {
            navbar.classList.add('bg-white', 'shadow-lg');
            navbar.classList.remove('bg-white/90', 'backdrop-blur-sm', 'shadow-md');
        }
        
        // Hide/show navbar on scroll direction (optional enhancement)
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scrolling down - hide navbar
            navbar.style.transform = 'translateY(-100%)';
        } else {
            // Scrolling up - show navbar
            navbar.style.transform = 'translateY(0)';
        }
        
        lastScrollTop = scrollTop;
    });
    
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (mobileMenu && !mobileMenu.classList.contains('hidden') && 
            !navbar.contains(event.target)) {
            mobileMenu.classList.add('hidden');
        }
    });
});
</script>

<style>
/* Ensure navbar stays on top during transforms */
#navbar {
    transform: translateZ(0);
    will-change: transform;
    transition: transform 0.3s ease-in-out, background-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
}

/* Fix for navbar overlapping content */
body {
    padding-top: 0rem;
}
</style>
