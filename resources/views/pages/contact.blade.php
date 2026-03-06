<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us - Excel Academy</title>
    <meta name="description" content="Get in touch with Excel Academy. Contact information, office hours, and a contact form to reach our team in Molepolole, Botswana.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-900 min-h-screen flex flex-col">
    <!-- Include Navigation -->
    @include('layouts.navigation')

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 py-4 pt-20">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 py-4 pt-20">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Main Content - Full width -->
    <main class="flex-grow pt-20">
        <!-- Hero Section - Full width -->
        <div class="relative w-full bg-cover bg-center py-16 md:py-24" style="background-image: url('https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900 to-indigo-800 opacity-90"></div>
            <div class="relative z-10 text-center w-full px-4">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">Contact Us</h1>
                    <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto">Get in touch with our friendly team</p>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <div>
                        <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Reach Out</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-6">Get In Touch</h2>
                        
                        <div class="space-y-6">
                            <div class="flex">
                                <div class="text-indigo-600 text-2xl mr-4">📍</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Address</h3>
                                    <p class="text-gray-600">123 Education Lane<br>Molepolole, Botswana</p>
                                </div>
                            </div>
                            
                            <div class="flex">
                                <div class="text-indigo-600 text-2xl mr-4">📞</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Phone</h3>
                                    <p class="text-gray-600">(267) 123 4567<br>(267) 987 6543</p>
                                </div>
                            </div>
                            
                            <div class="flex">
                                <div class="text-indigo-600 text-2xl mr-4">✉️</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Email</h3>
                                    <p class="text-gray-600">info@excelacademy.bw<br>admissions@excelacademy.bw</p>
                                </div>
                            </div>
                            
                            <div class="flex">
                                <div class="text-indigo-600 text-2xl mr-4">🕐</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Office Hours</h3>
                                    <p class="text-gray-600">Monday - Friday: 7:30 AM - 4:00 PM<br>Saturday: 9:00 AM - 12:00 PM</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">Connect With Us</h3>
                            <div class="flex space-x-4">
                                <a href="#" class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white hover:bg-blue-700 transition duration-300 transform hover:-translate-y-1">
                                    <span class="text-xl">f</span>
                                </a>
                                <a href="https://wa.me/1234567890" class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white hover:bg-green-600 transition duration-300 transform hover:-translate-y-1">
                                    <span class="text-xl">💬</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Send a Message</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-6">Contact Form</h2>
                        <form class="space-y-6" action="{{ route('contact.submit') }}" method="POST">
                            @csrf
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" id="name" name="name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" id="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <select id="subject" name="subject" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option>General Inquiry</option>
                                    <option>Admissions</option>
                                    <option>Academic Programs</option>
                                    <option>Transport Services</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                <textarea id="message" name="message" rows="5" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>
                            
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 transform hover:-translate-y-1 shadow-lg">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Our Location</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Find Us</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Located conveniently in Molepolole with easy access to major routes</p>
                </div>
                
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="h-96 bg-gray-200 flex items-center justify-center">
                        <div class="text-center">
                            <div class="text-indigo-600 text-6xl mb-4">📍</div>
                            <p class="text-gray-600 text-lg">Interactive Map Coming Soon</p>
                            <p class="text-gray-500 text-sm mt-2">123 Education Lane, Molepolole, Botswana</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="text-indigo-600 text-3xl mb-4">🚗</div>
                        <h3 class="text-xl font-bold mb-2">Transport Routes</h3>
                        <p class="text-gray-600">Serving Gaborone, Mogoditshane, Metsimotlhabe, and Thamaga</p>
                    </div>
                    
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="text-indigo-600 text-3xl mb-4">🅿️</div>
                        <h3 class="text-xl font-bold mb-2">Parking</h3>
                        <p class="text-gray-600">Ample parking available for visitors and parents</p>
                    </div>
                    
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="text-indigo-600 text-3xl mb-4">♿</div>
                        <h3 class="text-xl font-bold mb-2">Accessibility</h3>
                        <p class="text-gray-600">Fully accessible campus with wheelchair ramps</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Contacts -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Direct Lines</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Department Contacts</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Reach the right department directly</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1 text-center border border-gray-100">
                        <div class="text-indigo-600 text-4xl mb-4">👨‍🏫</div>
                        <h3 class="text-xl font-bold mb-2">Academics</h3>
                        <p class="text-gray-600 font-medium">(267) 123 4567 ext. 101</p>
                        <p class="text-gray-500 text-sm">academics@excelacademy.bw</p>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1 text-center border border-gray-100">
                        <div class="text-indigo-600 text-4xl mb-4">📝</div>
                        <h3 class="text-xl font-bold mb-2">Admissions</h3>
                        <p class="text-gray-600 font-medium">(267) 123 4567 ext. 102</p>
                        <p class="text-gray-500 text-sm">admissions@excelacademy.bw</p>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1 text-center border border-gray-100">
                        <div class="text-indigo-600 text-4xl mb-4">🚌</div>
                        <h3 class="text-xl font-bold mb-2">Transport</h3>
                        <p class="text-gray-600 font-medium">(267) 123 4567 ext. 103</p>
                        <p class="text-gray-500 text-sm">transport@excelacademy.bw</p>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1 text-center border border-gray-100">
                        <div class="text-indigo-600 text-4xl mb-4">👥</div>
                        <h3 class="text-xl font-bold mb-2">Student Affairs</h3>
                        <p class="text-gray-600 font-medium">(267) 123 4567 ext. 104</p>
                        <p class="text-gray-500 text-sm">students@excelacademy.bw</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="py-16 md:py-20 w-full bg-indigo-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Need Immediate Assistance?</h2>
                <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">Our friendly staff is ready to help you with any questions or concerns.</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="tel:+2671234567" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Call Us Now
                    </a>
                    <a href="mailto:info@excelacademy.bw" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Send Email
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    @include('layouts.footer')
</body>
</html>
