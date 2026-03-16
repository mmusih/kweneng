<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us - Kweneng International Secondary School</title>
    <meta name="description"
        content="Get in touch with Kweneng International Secondary School in Molepolole, Botswana. Contact details, office hours, WhatsApp, email, and directions.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        #navbar-spacer {
            transition: height .3s ease;
        }
    </style>
</head>

<body class="font-sans antialiased bg-white text-gray-900 min-h-screen flex flex-col">
    @include('layouts.navigation')
    <div id="navbar-spacer"></div>

    @if (session('success'))
        <div class="max-w-7xl mx-auto px-4 py-4 w-full">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-7xl mx-auto px-4 py-4 w-full">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <main class="flex-grow">
        <!-- Hero Section -->
        <div class="relative w-full bg-cover bg-center py-16 md:py-24"
            style="background-image: url('https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900 to-indigo-800 opacity-90"></div>
            <div class="relative z-10 text-center w-full px-4">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
                        Contact Kweneng International
                    </h1>
                    <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto">
                        We are here to assist with admissions, academic enquiries, general information, and school
                        communication.
                    </p>
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
                                    <h3 class="text-xl font-bold text-gray-800">Postal Address</h3>
                                    <p class="text-gray-600">
                                        P O Box 586<br>
                                        Molepolole, Botswana
                                    </p>
                                </div>
                            </div>

                            <div class="flex">
                                <div class="text-indigo-600 text-2xl mr-4">📞</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Phone</h3>
                                    <p class="text-gray-600">
                                        <a href="tel:+2675915015"
                                            class="hover:text-indigo-600 transition">5915015</a><br>
                                        <a href="tel:+2675915016" class="hover:text-indigo-600 transition">5915016</a>
                                    </p>
                                </div>
                            </div>

                            <div class="flex">
                                <div class="text-indigo-600 text-2xl mr-4">💬</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">WhatsApp</h3>
                                    <p class="text-gray-600">
                                        <a href="https://wa.me/26777738838" target="_blank" rel="noopener"
                                            class="hover:text-green-600 transition">
                                            77738838
                                        </a>
                                    </p>
                                </div>
                            </div>

                            <div class="flex">
                                <div class="text-indigo-600 text-2xl mr-4">✉️</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Email</h3>
                                    <p class="text-gray-600">
                                        <a href="mailto:kwenenginternational@gmail.com"
                                            class="hover:text-indigo-600 transition">kwenenginternational@gmail.com</a><br>
                                        <a href="mailto:info@kwenenginternational.com"
                                            class="hover:text-indigo-600 transition">info@kwenenginternational.com</a>
                                    </p>
                                </div>
                            </div>

                            <div class="flex">
                                <div class="text-indigo-600 text-2xl mr-4">🕐</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Office Hours</h3>
                                    <p class="text-gray-600">
                                        Monday - Friday: 7:30 AM - 4:00 PM
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">Connect With Us</h3>
                            <div class="flex space-x-4">
                                <a href="https://www.facebook.com/kwenenginternational" target="_blank" rel="noopener"
                                    class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white hover:bg-blue-700 transition duration-300 transform hover:-translate-y-1"
                                    aria-label="Facebook">
                                    <span class="text-xl font-bold">f</span>
                                </a>

                                <a href="https://wa.me/26777738838" target="_blank" rel="noopener"
                                    class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white hover:bg-green-600 transition duration-300 transform hover:-translate-y-1"
                                    aria-label="WhatsApp">
                                    <span class="text-xl">💬</span>
                                </a>

                                <a href="mailto:info@kwenenginternational.com"
                                    class="w-12 h-12 bg-indigo-600 rounded-full flex items-center justify-center text-white hover:bg-indigo-700 transition duration-300 transform hover:-translate-y-1"
                                    aria-label="Email">
                                    <span class="text-xl">✉</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div>
                        <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Send a
                            Message</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-6">Contact Form</h2>

                        <form class="space-y-6" action="{{ route('contact.submit') }}" method="POST">
                            @csrf

                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full
                                    Name</label>
                                <input type="text" id="name" name="name" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email
                                    Address</label>
                                <input type="email" id="email" name="email" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="subject"
                                    class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <select id="subject" name="subject"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option>General Inquiry</option>
                                    <option>Admissions</option>
                                    <option>Academic Programs</option>
                                    <option>Transport Services</option>
                                    <option>Portal Support</option>
                                    <option>Other</option>
                                </select>
                            </div>

                            <div>
                                <label for="message"
                                    class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                <textarea id="message" name="message" rows="5" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>

                            <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 transform hover:-translate-y-1 shadow-lg">
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
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        Located in Molepolole and accessible from major surrounding communities.
                    </p>
                </div>

                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="h-96 bg-gray-100 flex items-center justify-center">
                        <div class="text-center px-6">
                            <div class="text-indigo-600 text-6xl mb-4">📍</div>
                            <p class="text-gray-700 text-lg font-semibold">Molepolole, Botswana</p>
                            <p class="text-gray-500 text-sm mt-2">P O Box 586</p>
                            <a href="https://www.google.com/maps/search/Molepolole+Botswana" target="_blank"
                                rel="noopener"
                                class="inline-flex items-center mt-6 px-5 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
                                Open in Google Maps
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="text-indigo-600 text-3xl mb-4">🚌</div>
                        <h3 class="text-xl font-bold mb-2">Transport Routes</h3>
                        <p class="text-gray-600">
                            Serving Molepolole, Gaborone, Mogoditshane, Metsimotlhabe, and Thamaga.
                        </p>
                    </div>

                    <div
                        class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="text-indigo-600 text-3xl mb-4">🏫</div>
                        <h3 class="text-xl font-bold mb-2">School Visits</h3>
                        <p class="text-gray-600">
                            Contact the school in advance to arrange a visit or admissions enquiry.
                        </p>
                    </div>

                    <div
                        class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="text-indigo-600 text-3xl mb-4">📱</div>
                        <h3 class="text-xl font-bold mb-2">Fastest Response</h3>
                        <p class="text-gray-600">
                            WhatsApp is one of the quickest ways to reach the school for day-to-day enquiries.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Contact Channels -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Main Contact
                        Channels</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">How To Reach Us</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        Use the contact option that is most convenient for you.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <a href="tel:+2675915015"
                        class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1 text-center border border-gray-100">
                        <div class="text-indigo-600 text-4xl mb-4">📞</div>
                        <h3 class="text-xl font-bold mb-2">Phone</h3>
                        <p class="text-gray-600 font-medium">5915015</p>
                        <p class="text-gray-500 text-sm">Call the school office</p>
                    </a>

                    <a href="https://wa.me/26777738838" target="_blank" rel="noopener"
                        class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1 text-center border border-gray-100">
                        <div class="text-indigo-600 text-4xl mb-4">💬</div>
                        <h3 class="text-xl font-bold mb-2">WhatsApp</h3>
                        <p class="text-gray-600 font-medium">77738838</p>
                        <p class="text-gray-500 text-sm">Quick communication channel</p>
                    </a>

                    <a href="mailto:info@kwenenginternational.com"
                        class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1 text-center border border-gray-100">
                        <div class="text-indigo-600 text-4xl mb-4">✉️</div>
                        <h3 class="text-xl font-bold mb-2">Email</h3>
                        <p class="text-gray-600 font-medium">info@kwenenginternational.com</p>
                        <p class="text-gray-500 text-sm">General enquiries</p>
                    </a>

                    <a href="https://www.facebook.com/kwenenginternational" target="_blank" rel="noopener"
                        class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition duration-300 transform hover:-translate-y-1 text-center border border-gray-100">
                        <div class="text-indigo-600 text-4xl mb-4">f</div>
                        <h3 class="text-xl font-bold mb-2">Facebook</h3>
                        <p class="text-gray-600 font-medium">kwenenginternational</p>
                        <p class="text-gray-500 text-sm">School updates and engagement</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="py-16 md:py-20 w-full bg-indigo-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Need Immediate Assistance?</h2>
                <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">
                    Our team is ready to help with admissions, academic information, and general enquiries.
                </p>

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="tel:+2675915015"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Call Us Now
                    </a>

                    <a href="mailto:info@kwenenginternational.com"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Send Email
                    </a>

                    <a href="https://wa.me/26777738838" target="_blank" rel="noopener"
                        class="bg-white hover:bg-gray-100 text-green-700 font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 border border-green-200">
                        WhatsApp Us
                    </a>
                </div>
            </div>
        </div>
    </main>

    @include('layouts.footer')
</body>

</html>
