<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kweneng International Secondary School - Cambridge Secondary School</title>
    <meta name="description" content="Botswana's leading Cambridge curriculum secondary school with excellent academics and global recognition.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Additional custom styles */
        [x-cloak] { display: none !important; }
        
        /* Hero-specific styles */
        .hero-minimal {
            font-family: 'Inter', system-ui, sans-serif;
            background: #ffffff;
            color: #1e293b;
        }
        .hero-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }
        .hero-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 2rem;
        }
        .hero-text {
            flex: 1 1 400px;
            text-align: center;
        }
        .hero-image {
            flex: 1 1 400px;
            text-align: center;
        }
        @media (min-width: 992px) {
            .hero-text {
                text-align: left;
            }
        }
        .main-heading {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: #1e293b;
        }
        .text-primary {
            color: #2563eb;
        }
        .lead-text {
            font-size: 1.1rem;
            color: #4b5563;
            max-width: 580px;
            margin-bottom: 2rem;
            margin-left: auto;
            margin-right: auto;
        }
        @media (min-width: 992px) {
            .lead-text {
                margin-left: 0;
                margin-right: 0;
            }
        }
        .feature-list {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
            margin: 0 0 2rem;
            padding: 0;
        }
        @media (min-width: 992px) {
            .feature-list {
                justify-content: flex-start;
            }
        }
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #334155;
            font-weight: 500;
        }
        .feature-list i {
            color: #2563eb;
            font-size: 1.3rem;
        }
        .image-frame {
            position: relative;
            max-width: 550px;
            margin: 0 auto;
            border-radius: 2rem;
            overflow: hidden;
            box-shadow: 0 25px 40px -15px rgba(0,0,0,0.2);
        }
        .student-img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.4s ease;
        }
        .student-img:hover {
            transform: scale(1.02);
        }
        .est-overlay {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(37, 99, 235, 0.9);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 500;
            backdrop-filter: blur(4px);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .me-1 { margin-right: 0.25rem; }
        .hero-buttons {
            margin-top: 1rem;
        }
    </style>
    
    <!-- Additional fonts/icons for hero -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="font-sans antialiased bg-white text-gray-900">

    <!-- Include Navigation -->
    @include('layouts.navigation')

    <!-- Hero Section - New minimal design with all your buttons preserved -->
  <!-- Hero Section - New minimal design with all your buttons preserved -->
<div class="hero-minimal pt-10"> <!-- Changed from hero-minimal to pt-24 -->
    <div class="hero-container">
        <div class="hero-row">
            
            <!-- LEFT: heading, description, feature list, and ALL your buttons -->
            <div class="hero-text">
                <!-- main heading -->
                <h1 class="main-heading">
                    Nurturing Young Minds for a 
                    <span class="text-primary">Brighter Future</span>
                </h1>

                <!-- description -->
                <p class="lead-text">
                    Quality education from kindergarten through high school. 
                    Experienced teachers, modern facilities — the perfect environment 
                    for your child's growth.
                </p>

                <!-- feature list (two items from the minimal design) -->
                <ul class="feature-list">
                    <li><i class="bi bi-mortarboard-fill"></i> Cambridge Curriculum</li>
                    <li><i class="fas fa-bus"></i> Transport Available</li>
                </ul>

                <!-- ALL YOUR ORIGINAL BUTTONS - preserved exactly as they were -->
                <div class="flex flex-wrap justify-center lg:justify-start gap-4 hero-buttons">
                    <a href="{{ route('admissions') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105">
                        Apply Now
                    </a>
                    <a href="#" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105">
                        Download Prospectus
                    </a>
                    <a href="#" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105">
                        Rewrite Registration
                    </a>
                    <a href="{{ route('login') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105">
                        Academic Portal
                    </a>
                    <a href="#" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105">
                        Download Yearbook 2025
                    </a>
                </div>
            </div>

            <!-- RIGHT: image with est. overlay (from minimal design) -->
            <div class="hero-image">
                <div class="image-frame">
                    <img src="images/06.png"
                         alt="Happy students in classroom"
                         class="student-img">
                    <div class="est-overlay">
                        <i class="fas fa-school me-1"></i> Est. 2005
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- Trust Bar (unchanged) -->
    <div class="bg-gray-100 py-4">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap justify-center items-center gap-8 text-sm font-medium text-gray-700">
                <div class="flex items-center">
                    <span class="text-green-600 mr-2">✔</span> 95% Pass Rate
                </div>
                <div class="flex items-center">
                    <span class="text-green-600 mr-2">✔</span> 20+ Years Excellence
                </div>
                <div class="flex items-center">
                    <span class="text-green-600 mr-2">✔</span> 3× Top in World
                </div>
                <div class="flex items-center">
                    <span class="text-green-600 mr-2">✔</span> Transport Available
                </div>
                <div class="flex items-center">
                    <span class="text-green-600 mr-2">✔</span> Cambridge Curriculum
                </div>
            </div>
        </div>
    </div>

    <!-- Why Choose Us (unchanged) -->
    <div class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Why Choose Kweneng International</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Discover what makes us Botswana's premier Cambridge curriculum school</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <div class="text-indigo-600 text-4xl mb-4">📚</div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Academic Excellence</h3>
                    <p class="text-gray-600">Outstanding results in Cambridge International examinations with personalized learning approaches.</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <div class="text-indigo-600 text-4xl mb-4">🏆</div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">World-Class Achievements</h3>
                    <p class="text-gray-600">Ranked among top schools globally with students achieving perfect scores in international exams.</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <div class="text-indigo-600 text-4xl mb-4">👩‍🏫</div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Highly Qualified Teachers</h3>
                    <p class="text-gray-600">Expert educators with international qualifications committed to student success.</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <div class="text-indigo-600 text-4xl mb-4">🛡</div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Safe Learning Environment</h3>
                    <p class="text-gray-600">Secure campus with modern facilities and comprehensive student welfare programs.</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <div class="text-indigo-600 text-4xl mb-4">🚌</div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Transport Across Major Routes</h3>
                    <p class="text-gray-600">Reliable transportation services covering Molepolole, Gaborone, Mogoditshane, and surrounding areas.</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <div class="text-indigo-600 text-4xl mb-4">🌍</div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Global Perspective, Local Values</h3>
                    <p class="text-gray-600">International curriculum combined with strong emphasis on cultural heritage and values.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Top in the World (unchanged) -->
    <div class="py-16 bg-gradient-to-r from-indigo-700 to-purple-800 text-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <img src="images/07.png" 
                         alt="Student Achievement" 
                         class="rounded-lg shadow-2xl w-full">
                </div>
                <div>
                    <div class="inline-block bg-yellow-500 text-yellow-900 px-4 py-1 rounded-full text-sm font-bold mb-4">
                        👑 TOP IN THE WORLD
                    </div>
                    <h2 class="text-3xl font-bold mb-6">Ranked #1 in Cambridge IGCSE Mathematics Globally</h2>
                    <p class="text-xl mb-6">For the third consecutive year, our students have achieved outstanding results in international assessments, with 98% of our students scoring A* or A grades.</p>
                    <p class="mb-8">This achievement places us among the elite educational institutions worldwide and demonstrates our commitment to academic excellence.</p>
                    <a href="#" class="inline-block bg-white text-indigo-700 hover:bg-gray-100 font-bold py-3 px-6 rounded-lg transition duration-300">
                        View Academic Results →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Admissions Section (unchanged) -->
    <div class="py-16 bg-cover bg-center relative" style="background-image: url('images/08.png')">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center max-w-3xl mx-auto py-12">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Join Botswana's Leading Cambridge Secondary School</h2>
                <p class="text-xl text-gray-200 mb-10">Begin your journey to academic excellence with our world-class education program</p>
                
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('admissions') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 transform hover:scale-105">
                        Apply Now
                    </a>
                    <a href="#" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-8 rounded-lg transition duration-300 transform hover:scale-105">
                        Download Application Form
                    </a>
                    <a href="https://wa.me/26777738838" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-8 rounded-lg transition duration-300 transform hover:scale-105">
                        Speak to Admissions (WhatsApp)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Transport Coverage (unchanged) -->
    <div class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">🚌 TRANSPORT COVERAGE</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Serving Families Across Major Communities</p>
            </div>
            
            <div class="flex flex-wrap justify-center gap-6 text-lg font-medium">
                <div class="bg-white px-6 py-3 rounded-full shadow-md text-gray-800">Molepolole</div>
                <div class="bg-white px-6 py-3 rounded-full shadow-md text-gray-800">Gaborone</div>
                <div class="bg-white px-6 py-3 rounded-full shadow-md text-gray-800">Mogoditshane</div>
                <div class="bg-white px-6 py-3 rounded-full shadow-md text-gray-800">Metsimotlhabe</div>
                <div class="bg-white px-6 py-3 rounded-full shadow-md text-gray-800">Thamaga</div>
            </div>
        </div>
    </div>

    <!-- Footer (unchanged) -->
    @include('layouts.footer')
</body>
</html>