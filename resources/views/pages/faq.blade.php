<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Frequently Asked Questions - Kweneng International</title>
    <meta name="description" content="Find answers to common questions about Kweneng International Secondary School.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-white text-gray-900">
    @include('layouts.navigation')

    <div class="min-h-screen pt-16">
        <!-- Hero Section -->
        <div class="relative w-full bg-cover bg-center py-16 md:py-24"
            style="background-image: url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-indigo-800/85"></div>

            <div class="relative z-10 w-full px-4">
                <div class="max-w-7xl mx-auto text-center">
                    <span
                        class="inline-block bg-white/10 text-blue-100 px-4 py-2 rounded-full text-sm font-semibold tracking-wide uppercase">
                        Help & Information
                    </span>

                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mt-5 mb-4">
                        Frequently Asked Questions
                    </h1>

                    <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto">
                        Find answers to common questions about Kweneng International Secondary School.
                    </p>
                </div>
            </div>
        </div>

        <!-- FAQ Content -->
        <div class="py-16 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Need Answers?</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Common Questions</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        Browse through the questions below for quick information about our school, admissions, and
                        school life.
                    </p>
                </div>

                <div class="space-y-4">
                    @foreach ($faqs as $index => $faq)
                        <div class="faq-card bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                            <button
                                class="faq-button w-full flex justify-between items-center gap-4 p-5 md:p-6 text-left focus:outline-none focus:bg-gray-50 transition"
                                type="button" aria-expanded="false">
                                <span class="text-base md:text-lg font-semibold text-gray-900">
                                    {{ $faq['question'] }}
                                </span>

                                <span
                                    class="faq-icon flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 transition">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </button>

                            <div class="faq-content hidden px-5 md:px-6 pb-6 text-gray-600 leading-relaxed">
                                <p>{{ $faq['answer'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-14 text-center bg-gray-50 rounded-2xl p-8 border border-gray-100 shadow-sm">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Still have questions?</h3>
                    <p class="text-gray-600 mb-6 max-w-2xl mx-auto">
                        Our team is available to help with admissions, school procedures, and general enquiries.
                    </p>

                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="{{ route('contact') }}"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300 shadow-md">
                            Contact Us
                        </a>

                        <a href="https://wa.me/26777738838"
                            class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300 shadow-md">
                            WhatsApp Chat
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Resources -->
        <div class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Explore More</span>
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Helpful Resources</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        You may also find these pages useful.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <a href="{{ route('admissions') }}"
                        class="bg-white p-6 rounded-2xl shadow-md hover:shadow-lg transition duration-300 text-center border border-gray-100 hover:-translate-y-1">
                        <div class="text-indigo-600 text-4xl mb-4">📝</div>
                        <h3 class="text-xl font-bold mb-2">Admissions Process</h3>
                        <p class="text-gray-600">Learn about our application requirements, documents, and fees.</p>
                    </a>

                    <a href="{{ route('academics') }}"
                        class="bg-white p-6 rounded-2xl shadow-md hover:shadow-lg transition duration-300 text-center border border-gray-100 hover:-translate-y-1">
                        <div class="text-indigo-600 text-4xl mb-4">📚</div>
                        <h3 class="text-xl font-bold mb-2">Academic Programs</h3>
                        <p class="text-gray-600">Explore our Cambridge curriculum, subject offerings, and learning
                            approach.</p>
                    </a>

                    <a href="{{ route('about') }}"
                        class="bg-white p-6 rounded-2xl shadow-md hover:shadow-lg transition duration-300 text-center border border-gray-100 hover:-translate-y-1">
                        <div class="text-indigo-600 text-4xl mb-4">🏫</div>
                        <h3 class="text-xl font-bold mb-2">About Our School</h3>
                        <p class="text-gray-600">Read more about our mission, vision, and school identity.</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.footer')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqButtons = document.querySelectorAll('.faq-button');

            faqButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const content = this.nextElementSibling;
                    const icon = this.querySelector('.faq-icon svg');
                    const isHidden = content.classList.contains('hidden');

                    faqButtons.forEach(otherButton => {
                        const otherContent = otherButton.nextElementSibling;
                        const otherIcon = otherButton.querySelector('.faq-icon svg');

                        otherContent.classList.add('hidden');
                        otherButton.setAttribute('aria-expanded', 'false');

                        otherIcon.innerHTML =
                            '<path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />';
                    });

                    if (isHidden) {
                        content.classList.remove('hidden');
                        this.setAttribute('aria-expanded', 'true');
                        icon.innerHTML =
                            '<path fill-rule="evenodd" d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd" />';
                    }
                });
            });
        });
    </script>
</body>

</html>
