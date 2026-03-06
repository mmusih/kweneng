<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Frequently Asked Questions - Kweneng International</title>
    <meta name="description" content="Find answers to common questions about Kweneng International Secondary School">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-900">
    <!-- Include Navigation -->
    @include('layouts.navigation')

    <!-- Main content with padding for fixed navbar -->
    <div class="min-h-screen pt-16">
        <!-- Hero Section - Full width -->
        <div class="relative w-full bg-cover bg-center py-16 md:py-24" style="background-image: url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900 to-indigo-800 opacity-80"></div>
            <div class="container mx-auto px-4 relative z-10 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Frequently Asked Questions</h1>
                <p class="text-xl text-blue-100 max-w-3xl mx-auto">Find answers to common questions about Kweneng International</p>
            </div>
        </div>

        <!-- FAQ Content -->
        <div class="py-16">
            <div class="container mx-auto px-4 max-w-4xl">
                <div class="grid grid-cols-1 gap-6">
                    @foreach($faqs as $index => $faq)
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <button class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                            <span class="text-lg font-medium text-gray-900">{{ $faq['question'] }}</span>
                            <svg class="h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="px-6 pb-6 text-gray-600 hidden">
                            <p>{{ $faq['answer'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div class="mt-12 text-center">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Still Have Questions?</h3>
                    <p class="text-gray-600 mb-6">Our admissions team is here to help you with any additional inquiries</p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="{{ route('contact') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                            Contact Us
                        </a>
                        <a href="https://wa.me/26777738838" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                            WhatsApp Chat
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Resources -->
        <div class="py-16 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Helpful Resources</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Explore additional information about our school</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <a href="{{ route('admissions') }}" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300 text-center">
                        <div class="text-indigo-600 text-4xl mb-4">📝</div>
                        <h3 class="text-xl font-bold mb-2">Admissions Process</h3>
                        <p class="text-gray-600">Learn about our application requirements and timeline</p>
                    </a>
                    
                    <a href="{{ route('academics') }}" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300 text-center">
                        <div class="text-indigo-600 text-4xl mb-4">📚</div>
                        <h3 class="text-xl font-bold mb-2">Academic Programs</h3>
                        <p class="text-gray-600">Discover our curriculum and subject offerings</p>
                    </a>
                    
                    <a href="{{ route('policies') }}" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300 text-center">
                        <div class="text-indigo-600 text-4xl mb-4">📜</div>
                        <h3 class="text-xl font-bold mb-2">School Policies</h3>
                        <p class="text-gray-600">Review our guidelines and procedures</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    @include('layouts.footer')

    <!-- Simple FAQ Accordion Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqButtons = document.querySelectorAll('.bg-white.border button');
            
            faqButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const content = this.nextElementSibling;
                    const icon = this.querySelector('svg');
                    
                    if (content.classList.contains('hidden')) {
                        content.classList.remove('hidden');
                        icon.innerHTML = '<path fill-rule="evenodd" d="M7 10h6a1 1 0 110 2H7a1 1 0 110-2z" clip-rule="evenodd" />';
                    } else {
                        content.classList.add('hidden');
                        icon.innerHTML = '<path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />';
                    }
                });
            });
        });
    </script>
</body>
</html>
