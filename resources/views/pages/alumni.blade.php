<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alumni Network - Excel Academy</title>
    <meta name="description" content="Connect with Excel Academy alumni worldwide. Success stories, career achievements, and alumni network opportunities.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-900 min-h-screen flex flex-col">
    <!-- Include Navigation -->
    @include('layouts.navigation')


    <!-- Add this near the top of your alumni page, after the navigation -->
@if(session('success'))
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    </div>
@endif


    <!-- Main Content - Full width with padding for fixed navbar -->
    <main class="flex-grow pt-20 w-full">
        <!-- Hero Section - Full width -->
        <div class="relative w-full bg-cover bg-center py-16 md:py-24" style="background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-indigo-800/90"></div>
            <div class="relative z-10 text-center w-full px-4">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">Alumni Network</h1>
                    <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto">Connecting generations of Excel Academy graduates</p>
                </div>
            </div>
        </div>

        <!-- Alumni Stories -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Inspiring Journeys</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Alumni Success Stories</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Celebrating the achievements of our graduates around the world</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition duration-300 transform hover:-translate-y-1">
                        <div class="h-56 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                                 alt="Dr. Sarah Williams" 
                                 class="w-full h-full object-cover hover:scale-110 transition duration-500">
                        </div>
                        <div class="p-6">
                            <div class="flex items-center mb-3">
                                <span class="bg-indigo-100 text-indigo-600 text-xs font-semibold px-3 py-1 rounded-full">Class of 2010</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Dr. Sarah Williams</h3>
                            <p class="text-indigo-600 font-medium mb-3">Harvard Medical School</p>
                            <p class="text-gray-600 text-sm leading-relaxed">"Excel Academy gave me the foundation to pursue my passion for medicine. The rigorous academic program prepared me for the challenges of medical school."</p>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition duration-300 transform hover:-translate-y-1">
                        <div class="h-56 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                                 alt="Michael Johnson" 
                                 class="w-full h-full object-cover hover:scale-110 transition duration-500">
                        </div>
                        <div class="p-6">
                            <div class="flex items-center mb-3">
                                <span class="bg-indigo-100 text-indigo-600 text-xs font-semibold px-3 py-1 rounded-full">Class of 2015</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Michael Johnson</h3>
                            <p class="text-indigo-600 font-medium mb-3">MIT Engineering</p>
                            <p class="text-gray-600 text-sm leading-relaxed">"The problem-solving skills I developed at Excel Academy have been invaluable in my engineering career. I'm now working on sustainable energy solutions."</p>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition duration-300 transform hover:-translate-y-1">
                        <div class="h-56 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                                 alt="Emma Thompson" 
                                 class="w-full h-full object-cover hover:scale-110 transition duration-500">
                        </div>
                        <div class="p-6">
                            <div class="flex items-center mb-3">
                                <span class="bg-indigo-100 text-indigo-600 text-xs font-semibold px-3 py-1 rounded-full">Class of 2018</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Emma Thompson</h3>
                            <p class="text-indigo-600 font-medium mb-3">Oxford University</p>
                            <p class="text-gray-600 text-sm leading-relaxed">"The global perspective I gained at Excel Academy opened doors to international opportunities. I'm now pursuing a PhD in International Relations."</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Career Achievements -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Making an Impact</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Career Excellence</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Where our graduates are making an impact</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-5xl mb-4">🏥</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Healthcare</h3>
                        <div class="text-4xl font-bold text-indigo-600 mb-2">45%</div>
                        <p class="text-gray-600">Pursuing medical careers</p>
                        <div class="mt-3 text-sm text-gray-500">Doctors, nurses, researchers</div>
                    </div>
                    
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-5xl mb-4">💼</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Business</h3>
                        <div class="text-4xl font-bold text-indigo-600 mb-2">30%</div>
                        <p class="text-gray-600">Entrepreneurs & executives</p>
                        <div class="mt-3 text-sm text-gray-500">CEOs, founders, managers</div>
                    </div>
                    
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-5xl mb-4">🔬</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Research</h3>
                        <div class="text-4xl font-bold text-indigo-600 mb-2">15%</div>
                        <p class="text-gray-600">Scientists & researchers</p>
                        <div class="mt-3 text-sm text-gray-500">PhD candidates, lab directors</div>
                    </div>
                    
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-5xl mb-4">🏛</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Public Service</h3>
                        <div class="text-4xl font-bold text-indigo-600 mb-2">10%</div>
                        <p class="text-gray-600">Government & NGOs</p>
                        <div class="mt-3 text-sm text-gray-500">Policy makers, diplomats</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stay Connected -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-gradient-to-r from-indigo-700 to-indigo-800 rounded-2xl shadow-2xl overflow-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
                        <!-- Left side - Content -->
                        <div class="p-8 md:p-12 text-white">
                            <span class="text-indigo-200 font-semibold text-sm uppercase tracking-wider">Join the Community</span>
                            <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">Stay Connected</h2>
                            <p class="text-indigo-100 mb-6 text-lg">Join our alumni network to stay updated on events, reconnect with classmates, and give back to current students.</p>
                            
                            <ul class="space-y-4 mb-8">
                                <li class="flex items-start">
                                    <span class="text-green-400 mr-3 mt-1">✓</span>
                                    <div>
                                        <span class="font-semibold">Exclusive alumni events</span>
                                        <p class="text-indigo-200 text-sm">Reunions, networking mixers, and professional development</p>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-green-400 mr-3 mt-1">✓</span>
                                    <div>
                                        <span class="font-semibold">Career networking</span>
                                        <p class="text-indigo-200 text-sm">Connect with fellow alumni in your industry</p>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-green-400 mr-3 mt-1">✓</span>
                                    <div>
                                        <span class="font-semibold">Mentorship programs</span>
                                        <p class="text-indigo-200 text-sm">Guide and support current students</p>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="text-green-400 mr-3 mt-1">✓</span>
                                    <div>
                                        <span class="font-semibold">Quarterly newsletters</span>
                                        <p class="text-indigo-200 text-sm">Stay updated with alumni achievements</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Right side - Form (Replaced) -->
                        <div class="p-8 md:p-12 bg-white/10 backdrop-blur-sm">
                            <h3 class="text-2xl font-bold text-white mb-6">Register Today</h3>
                            <p class="text-indigo-100 mb-4">Fill out this form and our alumni team will contact you to complete your registration.</p>
                            <form action="{{ route('alumni.register-interest') }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-indigo-100 mb-1">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" required 
                                           class="w-full px-4 py-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-indigo-100 mb-1">Email Address</label>
                                    <input type="email" id="email" name="email" required 
                                           class="w-full px-4 py-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white">
                                </div>
                                <div>
                                    <label for="graduation_year" class="block text-sm font-medium text-indigo-100 mb-1">Graduation Year</label>
                                    <input type="text" id="graduation_year" name="graduation_year" required 
                                           class="w-full px-4 py-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-indigo-100 mb-1">Phone Number (Optional)</label>
                                    <input type="text" id="phone" name="phone" 
                                           class="w-full px-4 py-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white">
                                </div>
                                <button type="submit" 
                                        class="w-full bg-white text-indigo-700 hover:bg-indigo-50 font-bold py-3 px-4 rounded-lg transition duration-300 transform hover:-translate-y-1 shadow-lg mt-6">
                                    Submit Registration Interest
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Presence -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Worldwide Community</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Global Alumni Network</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Our graduates are making their mark around the world</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 max-w-4xl mx-auto">
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-5xl font-bold text-indigo-600 mb-2">25+</div>
                        <div class="w-16 h-1 bg-indigo-200 mx-auto my-3"></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Countries</h3>
                        <p class="text-gray-600">Alumni living and working internationally</p>
                    </div>
                    
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-5xl font-bold text-indigo-600 mb-2">500+</div>
                        <div class="w-16 h-1 bg-indigo-200 mx-auto my-3"></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Graduates</h3>
                        <p class="text-gray-600">Making a difference in their communities</p>
                    </div>
                    
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-5xl font-bold text-indigo-600 mb-2">15</div>
                        <div class="w-16 h-1 bg-indigo-200 mx-auto my-3"></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Years Strong</h3>
                        <p class="text-gray-600">Building our alumni community</p>
                    </div>
                </div>
                
                <!-- World Map Placeholder (optional decorative element) -->
                <div class="mt-12 text-center opacity-20">
                    <svg class="w-full max-w-4xl mx-auto" viewBox="0 0 1000 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M200,150 Q300,100 400,150 T600,120 T800,180" stroke="#4F46E5" stroke-width="2" fill="none"/>
                        <circle cx="250" cy="140" r="4" fill="#4F46E5"/>
                        <circle cx="450" cy="130" r="4" fill="#4F46E5"/>
                        <circle cx="650" cy="150" r="4" fill="#4F46E5"/>
                        <circle cx="850" cy="170" r="4" fill="#4F46E5"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Ready to Reconnect?</h2>
                <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">Whether you graduated last year or decades ago, you're always part of the Excel Academy family.</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="#" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Update Your Information
                    </a>
                    <a href="#" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Donate to Alumni Fund
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    @include('layouts.footer')
</body>
</html>