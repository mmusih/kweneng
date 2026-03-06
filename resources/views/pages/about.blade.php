<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About Kweneng International Secondary School</title>
    <meta name="description" content="Learn about Kweneng International Secondary School's history, mission, and leadership team.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-900">
    <!-- Include Navigation -->
    @include('layouts.navigation')

    <!-- Main content with padding for fixed navbar -->
    <div class="min-h-screen pt-16">
        <!-- Hero Section - Full width -->
        <div class="relative w-full bg-cover bg-center py-16 md:py-24" style="background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-indigo-800/90"></div>
            <div class="relative z-10 text-center w-full px-4">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">About Kweneng International</h1>
                    <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto">Discover our story, mission, and commitment to excellence</p>
                </div>
            </div>
        </div>

        <!-- Our Story -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div class="order-2 lg:order-1">
                        <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Our Journey</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-6">The Kweneng International Story</h2>
                        <div class="space-y-4 text-gray-600 leading-relaxed">
                            <p class="text-lg">Founded in 1995, Kweneng International began with a vision to provide world-class education rooted in Cambridge International standards while maintaining our commitment to Botswana's cultural values.</p>
                            <p>Over the past 25+ years, we have grown from a small secondary school to Botswana's premier Cambridge curriculum institution, consistently ranking among the top schools globally.</p>
                            <p>Today, we stand as a beacon of educational excellence, with state-of-the-art facilities, highly qualified teachers, and a student body that consistently achieves outstanding results.</p>
                        </div>
                        
                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-4 mt-8">
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-3xl font-bold text-indigo-600">25+</div>
                                <div class="text-sm text-gray-500">Years</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-3xl font-bold text-indigo-600">1000+</div>
                                <div class="text-sm text-gray-500">Students</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-3xl font-bold text-indigo-600">98%</div>
                                <div class="text-sm text-gray-500">Pass Rate</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-1 lg:order-2 mb-8 lg:mb-0">
                        <div class="relative">
                            <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                                 alt="School History" 
                                 class="rounded-2xl shadow-2xl w-full h-auto object-cover">
                            <div class="absolute -bottom-4 -left-4 bg-indigo-600 text-white p-4 rounded-lg shadow-lg">
                                <p class="font-bold text-lg">Est. 1995</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mission & Vision -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Our Purpose</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Mission & Vision</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white p-8 rounded-2xl shadow-lg">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-6">
                            <span class="text-3xl">🎯</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Our Mission</h3>
                        <p class="text-gray-600">To nurture globally competitive learners who embody integrity, demonstrate excellence, and contribute meaningfully to society through innovative education grounded in Cambridge International standards.</p>
                    </div>
                    
                    <div class="bg-white p-8 rounded-2xl shadow-lg">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-6">
                            <span class="text-3xl">👁️</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Our Vision</h3>
                        <p class="text-gray-600">To be Botswana's premier educational institution recognized internationally for academic excellence, character development, and preparing students to be leaders and innovators in a global society.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leadership Team -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Our Leaders</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Leadership Team</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" 
                                 alt="Headmaster" 
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="p-6 text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Dr. John Smith</h3>
                            <p class="text-indigo-600 font-medium mb-3">Headmaster</p>
                            <p class="text-gray-500 text-sm">PhD in Educational Leadership, 20+ years experience</p>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" 
                                 alt="Deputy Head" 
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="p-6 text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Ms. Sarah Johnson</h3>
                            <p class="text-indigo-600 font-medium mb-3">Deputy Head</p>
                            <p class="text-gray-500 text-sm">MA in Curriculum Development, 15+ years experience</p>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden md:col-span-2 lg:col-span-1 md:max-w-xl md:mx-auto lg:max-w-none">
                        <div class="h-64 overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" 
                                 alt="CEO" 
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="p-6 text-center">
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Mr. Robert Brown</h3>
                            <p class="text-indigo-600 font-medium mb-3">CEO</p>
                            <p class="text-gray-500 text-sm">MBA in Education Management, 18+ years experience</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Core Values -->
        <div class="py-16 md:py-20 w-full bg-indigo-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-200 font-semibold text-sm uppercase tracking-wider">What We Stand For</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-white mt-2 mb-4">Our Core Values</h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-indigo-600 p-6 rounded-xl text-center text-white">
                        <div class="text-4xl mb-4">🌟</div>
                        <h3 class="text-xl font-bold mb-2">Excellence</h3>
                        <p class="text-indigo-100">Striving for the highest standards in all our endeavors</p>
                    </div>
                    
                    <div class="bg-indigo-600 p-6 rounded-xl text-center text-white">
                        <div class="text-4xl mb-4">🤝</div>
                        <h3 class="text-xl font-bold mb-2">Integrity</h3>
                        <p class="text-indigo-100">Acting with honesty, transparency, and ethical behavior</p>
                    </div>
                    
                    <div class="bg-indigo-600 p-6 rounded-xl text-center text-white">
                        <div class="text-4xl mb-4">🌱</div>
                        <h3 class="text-xl font-bold mb-2">Growth</h3>
                        <p class="text-indigo-100">Fostering continuous learning and personal development</p>
                    </div>
                    
                    <div class="bg-indigo-600 p-6 rounded-xl text-center text-white">
                        <div class="text-4xl mb-4">❤️</div>
                        <h3 class="text-xl font-bold mb-2">Compassion</h3>
                        <p class="text-indigo-100">Caring for each individual's wellbeing and success</p>
                    </div>
                    
                    <div class="bg-indigo-600 p-6 rounded-xl text-center text-white">
                        <div class="text-4xl mb-4">💡</div>
                        <h3 class="text-xl font-bold mb-2">Innovation</h3>
                        <p class="text-indigo-100">Embracing creative solutions and modern approaches</p>
                    </div>
                    
                    <div class="bg-indigo-600 p-6 rounded-xl text-center text-white">
                        <div class="text-4xl mb-4">🌍</div>
                        <h3 class="text-xl font-bold mb-2">Global Citizenship</h3>
                        <p class="text-indigo-100">Preparing students to contribute to a connected world</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Ready to Join Kweneng International?</h2>
                <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">Experience the difference of a Cambridge education with Botswana's leading international school.</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('admissions') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg">
                        Apply Now
                    </a>
                    <a href="{{ route('contact') }}" class="bg-white hover:bg-gray-100 text-indigo-600 font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg border-2 border-indigo-600">
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    @include('layouts.footer')
</body>
</html>
