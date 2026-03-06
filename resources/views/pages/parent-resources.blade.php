<x-guest-layout>
    <div class="bg-white">
        <!-- Hero Section -->
        <div class="relative bg-cover bg-center py-24" style="background-image: url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900 to-indigo-800 opacity-80"></div>
            <div class="container mx-auto px-4 relative z-10 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Parent Resources</h1>
                <p class="text-xl text-blue-100 max-w-3xl mx-auto">Everything you need to support your child's education</p>
            </div>
        </div>

        <!-- Portal Access -->
        <div class="py-16">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Parent Portal</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Access your child's academic information and communicate with teachers</p>
                </div>
                
                <div class="max-w-4xl mx-auto bg-indigo-50 rounded-lg shadow-md p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-4">Login to Parent Portal</h3>
                            <p class="text-gray-600 mb-6">Access grades, attendance, assignments, and communicate with teachers through our secure portal.</p>
                            
                            <form class="space-y-4">
                                <div>
                                    <label for="portal-email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" id="portal-email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                
                                <div>
                                    <label for="portal-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                    <input type="password" id="portal-password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                
                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-md transition duration-300">
                                    Login to Portal
                                </button>
                                
                                <div class="text-center mt-4">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-800 text-sm">Forgot Password?</a>
                                </div>
                            </form>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-inner">
                            <h4 class="text-lg font-bold text-gray-800 mb-4">Portal Features</h4>
                            <ul class="space-y-3">
                                <li class="flex items-center">
                                    <span class="text-green-600 mr-3">✓</span>
                                    <span>Real-time grade updates</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="text-green-600 mr-3">✓</span>
                                    <span>Attendance tracking</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="text-green-600 mr-3">✓</span>
                                    <span>Assignment calendar</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="text-green-600 mr-3">✓</span>
                                    <span>Direct messaging with teachers</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="text-green-600 mr-3">✓</span>
                                    <span>Fee payment system</span>
                                </li>
                                <li class="flex items-center">
                                    <span class="text-green-600 mr-3">✓</span>
                                    <span>Event notifications</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- School Policies -->
        <div class="py-16 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Important Policies</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Essential guidelines for parents and students</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
                    <a href="{{ route('policies') }}" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300 block">
                        <div class="text-indigo-600 text-3xl mb-4">📜</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Code of Conduct</h3>
                        <p class="text-gray-600">Behavioral expectations and disciplinary procedures</p>
                    </a>
                    
                    <a href="{{ route('policies') }}" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300 block">
                        <div class="text-indigo-600 text-3xl mb-4">🛡</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Anti-Bullying Policy</h3>
                        <p class="text-gray-600">Zero tolerance approach to bullying and harassment</p>
                    </a>
                    
                    <a href="{{ route('policies') }}" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300 block">
                        <div class="text-indigo-600 text-3xl mb-4">📱</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Digital Citizenship</h3>
                        <p class="text-gray-600">Responsible technology use and online safety</p>
                    </a>
                    
                    <a href="{{ route('policies') }}" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300 block">
                        <div class="text-indigo-600 text-3xl mb-4">🚌</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Transport Policy</h3>
                        <p class="text-gray-600">Bus schedules, safety rules, and pickup/drop-off procedures</p>
                    </a>
                    
                    <a href="{{ route('policies') }}" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300 block">
                        <div class="text-indigo-600 text-3xl mb-4">🍽</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Meal Policy</h3>
                        <p class="text-gray-600">Nutrition guidelines and dietary accommodation procedures</p>
                    </a>
                    
                    <a href="{{ route('policies') }}" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition duration-300 block">
                        <div class="text-indigo-600 text-3xl mb-4">📅</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Attendance Policy</h3>
                        <p class="text-gray-600">Attendance requirements and leave application procedures</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Calendar & Events -->
        <div class="py-16">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Calendar & Events</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Stay informed about important dates and upcoming activities</p>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 max-w-6xl mx-auto">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-6">Academic Calendar</h3>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-lg font-bold text-gray-800">Term 1 - 2024</h4>
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">Current</span>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span>Start Date</span>
                                    <span class="font-medium">January 8, 2024</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Mid-Term Break</span>
                                    <span class="font-medium">February 12-16, 2024</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>End Date</span>
                                    <span class="font-medium">March 29, 2024</span>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <a href="{{ route('term-dates') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    View Full Academic Calendar →
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-6">Important Reminders</h3>
                        <div class="space-y-4">
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            <strong>Parent-Teacher Conferences:</strong> January 15-19, 2024
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            <strong>Science Fair:</strong> February 5, 2024
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-green-700">
                                            <strong>Report Cards:</strong> March 22, 2024
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Communication Channels -->
        <div class="py-16 bg-indigo-700 text-white">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold mb-4">Communication Channels</h2>
                    <p class="text-indigo-100 max-w-3xl mx-auto">Stay connected with school updates and your child's progress</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    <div class="text-center p-6 bg-indigo-800 rounded-lg">
                        <div class="text-4xl mb-4">📧</div>
                        <h3 class="text-xl font-bold mb-2">Email Newsletters</h3>
                        <p class="mb-4">Weekly newsletters with school updates, events, and important announcements</p>
                        <button class="bg-white text-indigo-700 hover:bg-gray-100 font-bold py-2 px-4 rounded-md transition duration-300 text-sm">
                            Subscribe
                        </button>
                    </div>
                    
                    <div class="text-center p-6 bg-indigo-800 rounded-lg">
                        <div class="text-4xl mb-4">📱</div>
                        <h3 class="text-xl font-bold mb-2">WhatsApp Groups</h3>
                        <p class="mb-4">Class-specific groups for instant communication with teachers and other parents</p>
                        <button class="bg-white text-indigo-700 hover:bg-gray-100 font-bold py-2 px-4 rounded-md transition duration-300 text-sm">
                            Join Group
                        </button>
                    </div>
                    
                    <div class="text-center p-6 bg-indigo-800 rounded-lg">
                        <div class="text-4xl mb-4">👨‍👩‍👧‍👦</div>
                        <h3 class="text-xl font-bold mb-2">Parent Association</h3>
                        <p class="mb-4">Volunteer opportunities and parent-led initiatives to enhance school life</p>
                        <button class="bg-white text-indigo-700 hover:bg-gray-100 font-bold py-2 px-4 rounded-md transition duration-300 text-sm">
                            Get Involved
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Volunteer Opportunities -->
        <div class="py-16">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Volunteer Opportunities</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Ways to contribute to our school community</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto">
                    <div class="bg-white p-6 rounded-lg shadow-md text-center">
                        <div class="text-indigo-600 text-3xl mb-4">📚</div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Library Helpers</h3>
                        <p class="text-gray-600 text-sm">Assist with book organization and reading programs</p>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-md text-center">
                        <div class="text-indigo-600 text-3xl mb-4">🎨</div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Art Support</h3>
                        <p class="text-gray-600 text-sm">Help with art projects and exhibition setup</p>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-md text-center">
                        <div class="text-indigo-600 text-3xl mb-4">⚽</div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Sports Coaching</h3>
                        <p class="text-gray-600 text-sm">Coach junior teams or assist during practices</p>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-md text-center">
                        <div class="text-indigo-600 text-3xl mb-4">🎪</div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Event Support</h3>
                        <p class="text-gray-600 text-sm">Help organize school events and celebrations</p>
                    </div>
                </div>
                
                <div class="mt-8 text-center">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                        Sign Up to Volunteer
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
