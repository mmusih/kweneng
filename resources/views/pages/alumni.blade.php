<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alumni Network - Kweneng International Secondary School</title>
    <meta name="description"
        content="Reconnect with Kweneng International Secondary School alumni, celebrate 20 years of the school, and register your alumni interest.">
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
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-7xl mx-auto px-4 py-4 w-full">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <main class="flex-grow w-full">
        <!-- Hero Section -->
        <div class="relative w-full bg-cover bg-center py-16 md:py-24"
            style="background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-indigo-800/90"></div>
            <div class="relative z-10 text-center w-full px-4">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
                        Kweneng International Alumni
                    </h1>
                    <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto">
                        Celebrating 20 years of Kweneng International Secondary School and the students who have been
                        part of its journey.
                    </p>
                </div>
            </div>
        </div>

        <!-- About Our Alumni -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">
                        Our Community
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">
                        Our Alumni
                    </h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        For two decades, Kweneng International Secondary School has prepared students for the Cambridge
                        curriculum and life beyond the classroom. Our alumni have continued their studies in Botswana
                        and abroad, entered diverse professions, and contributed positively to their communities.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">🎓</div>
                        <h3 class="text-xl font-bold mb-2">Higher Education</h3>
                        <p class="text-gray-600">
                            Many graduates continue their studies at universities and colleges within Botswana and
                            internationally.
                        </p>
                    </div>

                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">🌍</div>
                        <h3 class="text-xl font-bold mb-2">Global Opportunities</h3>
                        <p class="text-gray-600">
                            Alumni have pursued opportunities in different countries, reflecting the global outlook
                            encouraged by our Cambridge curriculum.
                        </p>
                    </div>

                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">🤝</div>
                        <h3 class="text-xl font-bold mb-2">Community Impact</h3>
                        <p class="text-gray-600">
                            Many former students contribute to their communities through business, education, public
                            service, and entrepreneurship.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stay Connected -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-gradient-to-r from-indigo-700 to-indigo-800 rounded-2xl shadow-2xl overflow-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
                        <!-- Left side -->
                        <div class="p-8 md:p-12 text-white">
                            <span class="text-indigo-200 font-semibold text-sm uppercase tracking-wider">
                                Join the Community
                            </span>
                            <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">Stay Connected</h2>
                            <p class="text-indigo-100 mb-6 text-lg">
                                We invite former students of Kweneng International Secondary School to reconnect with
                                the school community, stay informed about school developments, and support the next
                                generation of learners.
                            </p>

                            <ul class="space-y-4 mb-8">
                                <li class="flex items-start">
                                    <span class="text-green-400 mr-3 mt-1">✓</span>
                                    <div>
                                        <span class="font-semibold">Reconnect with classmates</span>
                                        <p class="text-indigo-200 text-sm">
                                            Stay in touch with former schoolmates and rebuild connections.
                                        </p>
                                    </div>
                                </li>

                                <li class="flex items-start">
                                    <span class="text-green-400 mr-3 mt-1">✓</span>
                                    <div>
                                        <span class="font-semibold">Receive school updates</span>
                                        <p class="text-indigo-200 text-sm">
                                            Keep up with school news, milestones, and alumni-related activities.
                                        </p>
                                    </div>
                                </li>

                                <li class="flex items-start">
                                    <span class="text-green-400 mr-3 mt-1">✓</span>
                                    <div>
                                        <span class="font-semibold">Support current students</span>
                                        <p class="text-indigo-200 text-sm">
                                            Inspire and encourage current learners through mentorship and engagement.
                                        </p>
                                    </div>
                                </li>

                                <li class="flex items-start">
                                    <span class="text-green-400 mr-3 mt-1">✓</span>
                                    <div>
                                        <span class="font-semibold">Build a stronger alumni network</span>
                                        <p class="text-indigo-200 text-sm">
                                            Help grow a lasting alumni community for Kweneng International.
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- Right side form -->
                        <div class="p-8 md:p-12 bg-white/10 backdrop-blur-sm">
                            <h3 class="text-2xl font-bold text-white mb-6">Register Today</h3>
                            <p class="text-indigo-100 mb-4">
                                Fill out this form and our alumni team will contact you to complete your registration.
                            </p>

                            <form action="{{ route('alumni.register-interest') }}" method="POST" class="space-y-4">
                                @csrf

                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-indigo-100 mb-1">
                                        Full Name
                                    </label>
                                    <input type="text" id="full_name" name="full_name" required
                                        class="w-full px-4 py-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-indigo-100 mb-1">
                                        Email Address
                                    </label>
                                    <input type="email" id="email" name="email" required
                                        class="w-full px-4 py-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white">
                                </div>

                                <div>
                                    <label for="graduation_year" class="block text-sm font-medium text-indigo-100 mb-1">
                                        Graduation Year
                                    </label>
                                    <input type="text" id="graduation_year" name="graduation_year" required
                                        class="w-full px-4 py-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white">
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-indigo-100 mb-1">
                                        Phone Number (Optional)
                                    </label>
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

        <!-- Alumni Community -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">
                        20 Years of Kweneng International
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">
                        A Growing Alumni Community
                    </h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        As Kweneng International Secondary School continues to grow, so does its alumni community. We
                        look forward to building a strong network of former students who remain connected to the school
                        and to each other.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-5xl font-bold text-indigo-600 mb-2">20</div>
                        <div class="w-16 h-1 bg-indigo-200 mx-auto my-3"></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Years</h3>
                        <p class="text-gray-600">Of school history and alumni growth</p>
                    </div>

                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-5xl font-bold text-indigo-600 mb-2">1</div>
                        <div class="w-16 h-1 bg-indigo-200 mx-auto my-3"></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">School Community</h3>
                        <p class="text-gray-600">One shared Kweneng International identity</p>
                    </div>

                    <div class="text-center p-6 bg-white rounded-xl shadow-md hover:shadow-xl transition duration-300">
                        <div class="text-5xl font-bold text-indigo-600 mb-2">∞</div>
                        <div class="w-16 h-1 bg-indigo-200 mx-auto my-3"></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Possibilities</h3>
                        <p class="text-gray-600">Many paths, careers, and futures shaped from here</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Ready to Reconnect?</h2>
                <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">
                    Whether you graduated recently or years ago, you are always part of the Kweneng International
                    family.
                </p>

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="#"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Update Your Information
                    </a>

                    <a href="{{ route('contact') }}"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Contact the School
                    </a>
                </div>
            </div>
        </div>
    </main>

    @include('layouts.footer')
</body>

</html>
