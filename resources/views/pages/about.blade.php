<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>About Kweneng International Secondary School</title>

    <meta name="description"
        content="Learn about Kweneng International Secondary School, our vision, mission, leadership and commitment to academic excellence.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-white text-gray-900">

    @include('layouts.navigation')

    <div class="min-h-screen pt-16">

        <!-- HERO -->
        <div class="relative w-full bg-cover bg-center py-20"
            style="background-image:url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1950&q=80')">

            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-indigo-800/90"></div>

            <div class="relative z-10 text-center px-4">
                <div class="max-w-6xl mx-auto">

                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        About Kweneng International
                    </h1>

                    <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                        A school community committed to academic excellence, discipline, and preparing learners for
                        global opportunities.
                    </p>

                </div>
            </div>
        </div>


        <!-- SCHOOL STORY -->
        <div class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">

                    <div>

                        <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">
                            Our School
                        </span>

                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-6">
                            The Kweneng International Story
                        </h2>

                        <p class="text-gray-600 mb-4 leading-relaxed">
                            Kweneng International Secondary School was founded with the vision of providing a strong
                            academic environment where learners can achieve excellence through discipline, hard work and
                            quality teaching.
                        </p>

                        <p class="text-gray-600 mb-4 leading-relaxed">
                            The school prepares pupils for the Cambridge IGCSE examinations and offers a wide selection
                            of subjects supported by qualified teachers and structured academic support.
                        </p>

                        <p class="text-gray-600 leading-relaxed">
                            Our philosophy is built on the belief that every child can achieve excellence when given the
                            right environment, strong academic guidance, parental support and the determination to
                            succeed.
                        </p>

                        <div class="grid grid-cols-3 gap-4 mt-8">

                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-indigo-600">IGCSE</div>
                                <div class="text-sm text-gray-500">Cambridge Exams</div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-indigo-600">Forms</div>
                                <div class="text-sm text-gray-500">1 – 5</div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-indigo-600">English</div>
                                <div class="text-sm text-gray-500">Instruction</div>
                            </div>

                        </div>

                    </div>

                    <div>
                        <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=800&q=80"
                            class="rounded-2xl shadow-xl w-full">
                    </div>

                </div>
            </div>
        </div>


        <!-- LEADERSHIP -->
        <div class="py-20 bg-gray-50">

            <div class="max-w-7xl mx-auto px-6 lg:px-8">

                <div class="text-center mb-14">

                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">
                        School Leadership
                    </span>

                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">
                        Leadership
                    </h2>

                    <p class="text-gray-600 max-w-3xl mx-auto">
                        The leadership of Kweneng International Secondary School continues a strong tradition of
                        academic excellence and disciplined learning.
                    </p>

                </div>


                <div class="flex justify-center">

                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden max-w-md w-full">

                        <div class="h-80 flex items-center justify-center bg-gray-100 p-8">
                            <div
                                class="w-64 h-64 rounded-full bg-white shadow-xl flex items-center justify-center overflow-hidden border-4 border-indigo-100">
                                <img src="{{ asset('images/patrick-gleeson.webp') }}" alt="Patrick Gleeson"
                                    class="w-full h-full object-cover object-center">
                            </div>
                        </div>

                        <div class="p-8 text-center">

                            <h3 class="text-2xl font-bold text-gray-900">
                                Mr Patrick Gleeson
                            </h3>

                            <p class="text-indigo-600 font-semibold mb-4">
                                Chief Executive Officer
                            </p>

                            <p class="text-gray-600 leading-relaxed">
                                Mr Patrick Gleeson leads Kweneng International Secondary School with a focus on
                                maintaining strong Cambridge academic standards and a disciplined learning culture built
                                on motivation and achievement.
                            </p>

                            <p class="text-gray-600 mt-4 leading-relaxed">
                                Under his leadership, the school continues to invest in quality teaching, modern
                                learning resources, and the development of globally competitive learners.
                            </p>

                        </div>

                    </div>

                </div>
            </div>
        </div>


        <!-- MISSION VISION -->
        <div class="py-20 bg-white">

            <div class="max-w-7xl mx-auto px-6 lg:px-8">

                <div class="text-center mb-12">

                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">
                        Our Purpose
                    </span>

                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2">
                        Mission & Vision
                    </h2>

                </div>


                <div class="grid md:grid-cols-2 gap-10">

                    <div class="bg-gray-50 p-8 rounded-xl">

                        <h3 class="text-2xl font-bold mb-4">
                            Our Vision
                        </h3>

                        <p class="text-gray-600 leading-relaxed">
                            Students at Kweneng International Secondary School will graduate having reached the highest
                            standards of excellence in their chosen fields of interest.
                        </p>

                    </div>


                    <div class="bg-gray-50 p-8 rounded-xl">

                        <h3 class="text-2xl font-bold mb-4">
                            Our Mission
                        </h3>

                        <p class="text-gray-600 leading-relaxed">
                            To provide a wide choice of subjects, high standards of teaching and learning facilities and
                            a motivational environment that enables pupils to excel academically and personally.
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <!-- CTA -->
        <div class="py-20 bg-gray-50">

            <div class="max-w-5xl mx-auto text-center px-6">

                <h2 class="text-3xl font-bold mb-4">
                    Join Kweneng International
                </h2>

                <p class="text-gray-600 mb-8">
                    Experience a Cambridge education supported by discipline, strong teaching and a culture of academic
                    excellence.
                </p>

                <div class="flex justify-center gap-4 flex-wrap">

                    <a href="{{ route('admissions') }}"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg shadow">
                        Apply Now
                    </a>

                    <a href="{{ route('contact') }}"
                        class="border-2 border-indigo-600 text-indigo-600 hover:bg-indigo-600 hover:text-white font-bold py-3 px-8 rounded-lg">
                        Contact Us
                    </a>

                </div>

            </div>

        </div>

    </div>


    @include('layouts.footer')

</body>

</html>
