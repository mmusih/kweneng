<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Academic Excellence - Excel Academy</title>
    <meta name="description" content="Cambridge International Curriculum delivering world-class education at Excel Academy">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-900 min-h-screen flex flex-col">
    <!-- Include Navigation -->
    @include('layouts.navigation')

    <!-- Main Content - Full width with padding for fixed navbar -->
    <main class="flex-grow pt-20 w-full">
        <!-- Hero Section - Full width -->
        <div class="relative w-full bg-cover bg-center py-16 md:py-24" style="background-image: url('https://images.unsplash.com/photo-1531482615713-2afd69097998?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-indigo-800/90"></div>
            <div class="relative z-10 text-center w-full px-4">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">Academic Excellence</h1>
                    <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto">Cambridge International Curriculum delivering world-class education</p>
                </div>
            </div>
        </div>

        <!-- Curriculum Overview -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Our Curriculum</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Cambridge International Curriculum</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Our curriculum prepares students for success in higher education and beyond through rigorous academic standards and holistic development.</p>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div class="order-2 lg:order-1">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Why Cambridge?</h3>
                        <p class="text-gray-600 mb-6">The Cambridge International curriculum is recognized worldwide for its academic rigor and comprehensive assessment methods. Our students benefit from:</p>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <span class="text-green-600 mr-3 mt-1">✓</span>
                                <span class="text-gray-700">Globally recognized qualifications accepted by universities worldwide</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-3 mt-1">✓</span>
                                <span class="text-gray-700">Rigorous assessment that develops critical thinking skills</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-3 mt-1">✓</span>
                                <span class="text-gray-700">Flexible curriculum allowing personalized learning paths</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-3 mt-1">✓</span>
                                <span class="text-gray-700">Focus on inquiry-based learning and problem-solving</span>
                            </li>
                        </ul>
                    </div>
                    <div class="order-1 lg:order-2 mb-8 lg:mb-0">
                        <img src="https://images.unsplash.com/photo-1589763639855-63ca1c61a15f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             alt="Curriculum" 
                             class="rounded-2xl shadow-2xl w-full h-auto object-cover">
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Offerings -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">What We Offer</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Subject Offerings</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Comprehensive subject choices across sciences, humanities, languages, and creative disciplines</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                    <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">🔬</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Sciences</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• Biology</li>
                            <li>• Chemistry</li>
                            <li>• Physics</li>
                            <li>• Environmental Management</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">📊</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Mathematics</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• Mathematics</li>
                            <li>• Additional Mathematics</li>
                            <li>• Statistics</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">🌐</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Languages</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• English</li>
                            <li>• French</li>
                            <li>• Setswana</li>
                            <li>• Literature</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">🏛</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Humanities</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• History</li>
                            <li>• Geography</li>
                            <li>• Economics</li>
                            <li>• Business Studies</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">🎨</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Creative Arts</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• Art & Design</li>
                            <li>• Music</li>
                            <li>• Drama</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300">
                        <div class="text-indigo-600 text-4xl mb-4">💻</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Technology</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>• Computer Science</li>
                            <li>• ICT</li>
                            <li>• Design & Technology</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Learning Approach -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div>
                        <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">How We Teach</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-6">Our Learning Approach</h2>
                        <p class="text-gray-600 mb-6">We believe in fostering deep understanding through inquiry-based learning that encourages critical thinking, creativity, and collaboration.</p>
                        
                        <div class="space-y-4">
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                <div class="text-green-600 text-2xl mr-4">✓</div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Student-Centered Learning</h3>
                                    <p class="text-gray-600">Active participation and ownership of learning process</p>
                                </div>
                            </div>
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                <div class="text-green-600 text-2xl mr-4">✓</div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Critical Thinking</h3>
                                    <p class="text-gray-600">Developing analytical and problem-solving skills</p>
                                </div>
                            </div>
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                <div class="text-green-600 text-2xl mr-4">✓</div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Collaborative Projects</h3>
                                    <p class="text-gray-600">Teamwork and communication skills development</p>
                                </div>
                            </div>
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                <div class="text-green-600 text-2xl mr-4">✓</div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Technology Integration</h3>
                                    <p class="text-gray-600">Modern tools enhancing learning experiences</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <img src="https://images.unsplash.com/photo-1589763639855-63ca1c61a15f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             alt="Learning Approach" 
                             class="rounded-2xl shadow-2xl w-full h-auto object-cover">
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessment Methods -->
        <div class="py-16 md:py-20 w-full bg-gradient-to-r from-indigo-700 to-indigo-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-200 font-semibold text-sm uppercase tracking-wider">How We Measure</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-white mt-2 mb-4">Assessment Excellence</h2>
                    <p class="text-indigo-100 max-w-3xl mx-auto">Our comprehensive assessment approach ensures holistic evaluation of student progress</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
                    <div class="text-center p-6 bg-indigo-600/50 backdrop-blur-sm rounded-xl hover:bg-indigo-600 transition duration-300">
                        <div class="text-5xl mb-4">📝</div>
                        <h3 class="text-xl font-bold text-white mb-2">Formative Assessment</h3>
                        <p class="text-indigo-100">Ongoing evaluation through projects, presentations, and peer reviews</p>
                    </div>
                    
                    <div class="text-center p-6 bg-indigo-600/50 backdrop-blur-sm rounded-xl hover:bg-indigo-600 transition duration-300">
                        <div class="text-5xl mb-4">📊</div>
                        <h3 class="text-xl font-bold text-white mb-2">Summative Assessment</h3>
                        <p class="text-indigo-100">Standardized testing aligned with Cambridge International benchmarks</p>
                    </div>
                    
                    <div class="text-center p-6 bg-indigo-600/50 backdrop-blur-sm rounded-xl hover:bg-indigo-600 transition duration-300">
                        <div class="text-5xl mb-4">📈</div>
                        <h3 class="text-xl font-bold text-white mb-2">Progress Tracking</h3>
                        <p class="text-indigo-100">Continuous monitoring with detailed feedback for improvement</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Begin Your Academic Journey</h2>
                <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">Join our Cambridge International program and unlock your potential for global success.</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('admissions') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Apply Now
                    </a>
                    <a href="{{ route('contact') }}" class="bg-white hover:bg-gray-100 text-indigo-600 font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 border-2 border-indigo-600">
                        Request Information
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    @include('layouts.footer')
</body>
</html>