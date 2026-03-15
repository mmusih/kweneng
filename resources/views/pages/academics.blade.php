<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Academics - Kweneng International Secondary School</title>
    <meta name="description"
        content="Explore the Cambridge IGCSE curriculum, subject offerings, academic structure, and learning approach at Kweneng International Secondary School.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-white text-gray-900 min-h-screen flex flex-col">
    @include('layouts.navigation')

    <main class="flex-grow pt-20 w-full">
        <!-- Hero Section -->
        <div class="relative w-full bg-cover bg-center py-16 md:py-24"
            style="background-image: url('https://images.unsplash.com/photo-1531482615713-2afd69097998?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-indigo-800/90"></div>
            <div class="relative z-10 text-center w-full px-4">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">Academic Excellence</h1>
                    <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto">
                        Cambridge IGCSE preparation, strong subject choice, and a disciplined learning environment.
                    </p>
                </div>
            </div>
        </div>

        <!-- Curriculum Overview -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Our Curriculum</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Cambridge IGCSE Pathway</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        Kweneng International Secondary School prepares pupils for the IGCSE examinations of the
                        Cambridge Examinations Syndicate, with English as the language of instruction and communication
                        throughout the school.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div class="order-2 lg:order-1">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Why our academic approach works</h3>
                        <p class="text-gray-600 mb-6">
                            Our academic structure combines strong subject choice, qualified teachers, regular
                            assignments,
                            and a motivational environment to help pupils reach a high standard of excellence.
                        </p>

                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <span class="text-green-600 mr-3 mt-1">✓</span>
                                <span class="text-gray-700">Preparation for Cambridge IGCSE examinations through a clear
                                    long-term academic pathway</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-3 mt-1">✓</span>
                                <span class="text-gray-700">A wide choice of subjects across languages, sciences,
                                    business, humanities, and technology</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-3 mt-1">✓</span>
                                <span class="text-gray-700">High standards of teaching, learning facilities, and
                                    academic
                                    support</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-3 mt-1">✓</span>
                                <span class="text-gray-700">Regular homework, classwork, tests, and examinations that
                                    promote consistency and discipline</span>
                            </li>
                        </ul>
                    </div>

                    <div class="order-1 lg:order-2 mb-8 lg:mb-0">
                        <img src="https://images.unsplash.com/photo-1589763639855-63ca1c61a15f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                            alt="Curriculum" class="rounded-2xl shadow-2xl w-full h-auto object-cover">
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Structure -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Academic
                        Structure</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Forms 1 to 5 pathway</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        Our academic journey is structured to progressively prepare learners for success in the
                        Cambridge
                        IGCSE examinations.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-indigo-600 text-4xl mb-4">1</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Form 1</h3>
                        <p class="text-gray-600">
                            Begins the five-year programme leading toward the Cambridge IGCSE examination pathway.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-indigo-600 text-4xl mb-4">2</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Form 2</h3>
                        <p class="text-gray-600">
                            Continues core subject development, with strong performers potentially accelerated into Form
                            4.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-indigo-600 text-4xl mb-4">3</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Form 3</h3>
                        <p class="text-gray-600">
                            Strengthens subject mastery and supports learners progressing toward upper secondary IGCSE
                            preparation.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <div class="text-indigo-600 text-4xl mb-4">4-5</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Forms 4 & 5</h3>
                        <p class="text-gray-600">
                            Focused IGCSE preparation with subject selection, examination readiness, and stronger
                            academic specialization.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Offerings -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Subject Choice</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Subjects offered</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        Students benefit from a wide subject range that supports both strong academic foundations and
                        future specialization.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                    <div class="bg-gray-50 p-6 rounded-xl shadow-sm hover:shadow-lg transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">🔬</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Sciences</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• Biology</li>
                            <li>• Chemistry</li>
                            <li>• Physics</li>
                            <li>• Agriculture</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 p-6 rounded-xl shadow-sm hover:shadow-lg transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">📘</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Languages</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• English Language</li>
                            <li>• English Literature</li>
                            <li>• French</li>
                            <li>• Setswana</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 p-6 rounded-xl shadow-sm hover:shadow-lg transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">➗</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Mathematics</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• Mathematics</li>
                            <li>• Additional Mathematics</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 p-6 rounded-xl shadow-sm hover:shadow-lg transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">💼</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Business & Commercial</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• Business Studies</li>
                            <li>• Accounting</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 p-6 rounded-xl shadow-sm hover:shadow-lg transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">🌍</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Humanities & Perspectives</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• Geography</li>
                            <li>• Global Perspectives</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 p-6 rounded-xl shadow-sm hover:shadow-lg transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">💻</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Technology</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• Computer Studies</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form by Form -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">By Form</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Academic focus by level</h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white p-8 rounded-2xl shadow-lg">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Forms 1 and 2</h3>
                        <p class="text-gray-600 mb-4">
                            In the lower forms, learners build a broad academic base across languages, mathematics,
                            sciences, business-related subjects, geography, and computer studies.
                        </p>
                        <ul class="text-gray-600 space-y-2">
                            <li>• English Language</li>
                            <li>• English Literature</li>
                            <li>• French or Setswana</li>
                            <li>• Mathematics</li>
                            <li>• Computer Studies</li>
                            <li>• Physics, Chemistry, Biology</li>
                            <li>• Agriculture</li>
                            <li>• Geography</li>
                            <li>• Business Studies</li>
                            <li>• Accounting</li>
                        </ul>
                    </div>

                    <div class="bg-white p-8 rounded-2xl shadow-lg">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Forms 4 and 5</h3>
                        <p class="text-gray-600 mb-4">
                            In the upper forms, students narrow their academic path and prepare directly for IGCSE
                            examinations, usually taking eight subjects from the available selection.
                        </p>
                        <ul class="text-gray-600 space-y-2">
                            <li>• English</li>
                            <li>• English Literature</li>
                            <li>• Mathematics</li>
                            <li>• French or Setswana</li>
                            <li>• Biology</li>
                            <li>• Accounts</li>
                            <li>• Business Studies</li>
                            <li>• Agriculture</li>
                            <li>• Chemistry</li>
                            <li>• Global Perspectives</li>
                            <li>• Physics</li>
                            <li>• Additional Mathematics</li>
                            <li>• Computer Studies</li>
                            <li>• Geography</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Learning & Assessment -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div>
                        <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">How We
                            Teach</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-6">Learning, homework, and
                            assessment</h2>
                        <p class="text-gray-600 mb-6">
                            Our academic programme values consistent classwork, homework, discussion, tests, and end of
                            term examinations as part of a disciplined learning culture.
                        </p>

                        <div class="space-y-4">
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                <div class="text-green-600 text-2xl mr-4">✓</div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Regular homework</h3>
                                    <p class="text-gray-600">Homework is treated as a vital part of the academic
                                        programme.</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                <div class="text-green-600 text-2xl mr-4">✓</div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Frequent assignments</h3>
                                    <p class="text-gray-600">Assignments and class exercises help reinforce learning
                                        and accountability.</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                <div class="text-green-600 text-2xl mr-4">✓</div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Continuous assessment</h3>
                                    <p class="text-gray-600">Assessment includes homework, oral work, monthly tests,
                                        and end of term examinations.</p>
                                </div>
                            </div>

                            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                <div class="text-green-600 text-2xl mr-4">✓</div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Parental support</h3>
                                    <p class="text-gray-600">Parents are encouraged to take an active role in
                                        supporting academic progress.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <img src="https://images.unsplash.com/photo-1588072432836-e10032774350?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                            alt="Learning Approach" class="rounded-2xl shadow-2xl w-full h-auto object-cover">
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Structure -->
        <div class="py-16 md:py-20 w-full bg-gradient-to-r from-indigo-700 to-indigo-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-200 font-semibold text-sm uppercase tracking-wider">School Day</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-white mt-2 mb-4">A structured academic routine</h2>
                    <p class="text-indigo-100 max-w-3xl mx-auto">
                        A clear daily routine supports punctuality, productive study habits, and focused classroom
                        learning.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
                    <div class="text-center p-6 bg-indigo-600/50 backdrop-blur-sm rounded-xl">
                        <div class="text-5xl mb-4">⏰</div>
                        <h3 class="text-xl font-bold text-white mb-2">Morning Start</h3>
                        <p class="text-indigo-100">Students are expected to be punctual, with the school day beginning
                            early.</p>
                    </div>

                    <div class="text-center p-6 bg-indigo-600/50 backdrop-blur-sm rounded-xl">
                        <div class="text-5xl mb-4">📖</div>
                        <h3 class="text-xl font-bold text-white mb-2">Academic Focus</h3>
                        <p class="text-indigo-100">The school day is structured around lessons, breaks, and focused
                            academic engagement.</p>
                    </div>

                    <div class="text-center p-6 bg-indigo-600/50 backdrop-blur-sm rounded-xl">
                        <div class="text-5xl mb-4">⚽</div>
                        <h3 class="text-xl font-bold text-white mb-2">Beyond Lessons</h3>
                        <p class="text-indigo-100">Extra-curricular activities and structured support are part of
                            school life.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Begin your academic journey with us</h2>
                <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">
                    Join a school that combines Cambridge academic preparation, strong teaching, and a culture of
                    excellence.
                </p>

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('admissions') }}"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Apply Now
                    </a>

                    <a href="{{ route('contact') }}"
                        class="bg-white hover:bg-gray-100 text-indigo-600 font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 border-2 border-indigo-600">
                        Request Information
                    </a>
                </div>
            </div>
        </div>
    </main>

    @include('layouts.footer')
</body>

</html>
