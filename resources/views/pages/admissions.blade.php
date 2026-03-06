<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admissions - Join Excel Academy</title>
    <meta name="description" content="Begin your journey to academic excellence at Excel Academy. Simple 4-step admission process, requirements, tuition fees and important dates.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* WhatsApp color utility */
        .bg-whatsapp-500 {
            background-color: #25D366;
        }
        .bg-whatsapp-600 {
            background-color: #20B957;
        }
        .hover\:bg-whatsapp-600:hover {
            background-color: #20B957;
        }
    </style>
</head>
<body class="font-sans antialiased bg-white text-gray-900 min-h-screen flex flex-col">
    <!-- Include Navigation -->
    @include('layouts.navigation')

    <!-- Main Content - Full width with padding for fixed navbar -->
    <main class="flex-grow pt-20 w-full">
        <!-- Hero Section - Full width -->
        <div class="relative w-full bg-cover bg-center py-16 md:py-24" style="background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-indigo-800/90"></div>
            <div class="relative z-10 text-center w-full px-4">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">Join Excel Academy</h1>
                    <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto">Begin your journey to academic excellence today</p>
                </div>
            </div>
        </div>

        <!-- Admissions Process -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Simple Process</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">How to Apply</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Follow our simple 4-step process to become part of our learning community</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
                    <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">1</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Download Form</h3>
                        <p class="text-gray-600">Get our application form from our website or admissions office</p>
                    </div>
                    
                    <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">2</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Submit Documents</h3>
                        <p class="text-gray-600">Complete form and attach required supporting documents</p>
                    </div>
                    
                    <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">3</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Assessment</h3>
                        <p class="text-gray-600">Participate in entrance assessment and interview</p>
                    </div>
                    
                    <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">4</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Offer & Enrollment</h3>
                        <p class="text-gray-600">Receive offer and complete enrollment formalities</p>
                    </div>
                </div>
                
                <div class="mt-12 text-center">
                    <a href="#" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Download Application Form
                    </a>
                </div>
            </div>
        </div>

        <!-- Requirements -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">What You Need</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Admission Requirements</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Essential criteria for prospective students</p>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                    <div class="bg-white p-8 rounded-2xl shadow-lg">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                            <span class="bg-indigo-100 p-2 rounded-lg mr-3">📚</span>
                            Academic Requirements
                        </h3>
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <span class="text-green-600 text-xl mr-3 mt-1">✓</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Previous Academic Records</h4>
                                    <p class="text-gray-600">Official transcripts from previous school</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 text-xl mr-3 mt-1">✓</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Entrance Assessment</h4>
                                    <p class="text-gray-600">Mathematics and English proficiency tests</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 text-xl mr-3 mt-1">✓</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Interview</h4>
                                    <p class="text-gray-600">Meeting with admissions committee</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 text-xl mr-3 mt-1">✓</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Recommendation Letters</h4>
                                    <p class="text-gray-600">From current teachers or school principal</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-8 rounded-2xl shadow-lg">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                            <span class="bg-indigo-100 p-2 rounded-lg mr-3">📄</span>
                            Required Documents
                        </h3>
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <span class="text-indigo-600 text-xl mr-3 mt-1">📋</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Completed Application Form</h4>
                                    <p class="text-gray-600">Signed by parent/guardian</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="text-indigo-600 text-xl mr-3 mt-1">📄</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Birth Certificate</h4>
                                    <p class="text-gray-600">Copy of official birth certificate</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="text-indigo-600 text-xl mr-3 mt-1">📸</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Passport Photos</h4>
                                    <p class="text-gray-600">4 recent color passport-sized photographs</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="text-indigo-600 text-xl mr-3 mt-1">💳</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Medical Certificate</h4>
                                    <p class="text-gray-600">Health clearance from registered medical practitioner</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Important Dates -->
        <div class="py-16 md:py-20 w-full bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Mark Your Calendar</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Important Dates</h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">Key deadlines for the upcoming academic year</p>
                </div>
                
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-indigo-600 to-indigo-700 text-white">
                                <tr>
                                    <th class="py-4 px-6 text-left font-semibold">Event</th>
                                    <th class="py-4 px-6 text-left font-semibold">Date</th>
                                    <th class="py-4 px-6 text-left font-semibold hidden md:table-cell">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="py-4 px-6 font-medium">Application Deadline</td>
                                    <td class="py-4 px-6 text-indigo-600 font-semibold">March 15, 2024</td>
                                    <td class="py-4 px-6 text-gray-600 hidden md:table-cell">All applications must be submitted</td>
                                </tr>
                                <tr class="bg-gray-50 hover:bg-gray-100 transition duration-150">
                                    <td class="py-4 px-6 font-medium">Entrance Assessments</td>
                                    <td class="py-4 px-6 text-indigo-600 font-semibold">March 25-27, 2024</td>
                                    <td class="py-4 px-6 text-gray-600 hidden md:table-cell">Written tests and interviews</td>
                                </tr>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="py-4 px-6 font-medium">Offer Notifications</td>
                                    <td class="py-4 px-6 text-indigo-600 font-semibold">April 10, 2024</td>
                                    <td class="py-4 px-6 text-gray-600 hidden md:table-cell">Acceptance letters distributed</td>
                                </tr>
                                <tr class="bg-gray-50 hover:bg-gray-100 transition duration-150">
                                    <td class="py-4 px-6 font-medium">Enrollment Deadline</td>
                                    <td class="py-4 px-6 text-indigo-600 font-semibold">April 25, 2024</td>
                                    <td class="py-4 px-6 text-gray-600 hidden md:table-cell">Final enrollment and fee payment</td>
                                </tr>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="py-4 px-6 font-medium">New Academic Year Begins</td>
                                    <td class="py-4 px-6 text-indigo-600 font-semibold">May 1, 2024</td>
                                    <td class="py-4 px-6 text-gray-600 hidden md:table-cell">First day of classes</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mt-8 text-center">
                    <a href="{{ route('term-dates') }}" class="text-indigo-600 hover:text-indigo-800 font-medium inline-flex items-center">
                        View Full Academic Calendar 
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Tuition & Fees -->
        <div class="py-16 md:py-20 w-full bg-gradient-to-r from-indigo-700 to-indigo-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-200 font-semibold text-sm uppercase tracking-wider">Investment in Education</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-white mt-2 mb-4">Tuition & Fees</h2>
                    <p class="text-indigo-100 max-w-3xl mx-auto">Transparent pricing for quality education</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 max-w-5xl mx-auto">
                    <div class="bg-white text-gray-800 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition duration-300 transform hover:-translate-y-1">
                        <div class="text-indigo-600 text-4xl mb-4">💰</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Annual Tuition</h3>
                        <div class="text-3xl font-bold text-indigo-600 mb-4">P85,000</div>
                        <ul class="text-sm space-y-2 border-t border-gray-100 pt-4">
                            <li class="flex justify-between">
                                <span class="text-gray-600">Tuition Fees</span>
                                <span class="font-semibold">P70,000</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-gray-600">Facilities & Resources</span>
                                <span class="font-semibold">P10,000</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-gray-600">Activities & Programs</span>
                                <span class="font-semibold">P5,000</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-white text-gray-800 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition duration-300 transform hover:-translate-y-1">
                        <div class="text-indigo-600 text-4xl mb-4">➕</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Additional Costs</h3>
                        <div class="text-3xl font-bold text-indigo-600 mb-4">Variable</div>
                        <ul class="text-sm space-y-2 border-t border-gray-100 pt-4">
                            <li class="flex justify-between">
                                <span class="text-gray-600">Transport Service</span>
                                <span class="font-semibold">P8,000/year</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-gray-600">School Uniform</span>
                                <span class="font-semibold">P3,500/set</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-gray-600">Textbooks & Materials</span>
                                <span class="font-semibold">P4,000/year</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-white text-gray-800 rounded-2xl p-6 shadow-xl hover:shadow-2xl transition duration-300 transform hover:-translate-y-1">
                        <div class="text-indigo-600 text-4xl mb-4">💳</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Payment Options</h3>
                        <div class="text-3xl font-bold text-indigo-600 mb-4">Flexible</div>
                        <ul class="text-sm space-y-2 border-t border-gray-100 pt-4">
                            <li class="flex items-start">
                                <span class="text-green-600 mr-2">✓</span>
                                <span class="text-gray-600">Annual Payment (5% discount)</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-2">✓</span>
                                <span class="text-gray-600">Termly Payment</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-green-600 mr-2">✓</span>
                                <span class="text-gray-600">Monthly Installments</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-8 text-center">
                    <p class="text-indigo-100 text-sm">All fees are reviewed annually and subject to change</p>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="py-16 md:py-20 w-full bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Ready to Begin Your Journey?</h2>
                <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">Join hundreds of students who have chosen Excel Academy for their academic excellence</p>
                
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="#" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Apply Now
                    </a>
                    <a href="https://wa.me/1234567890" class="bg-[#25D366] hover:bg-[#20B957] text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                <path d="M12 0C5.373 0 0 5.373 0 12c0 6.628 5.373 12 12 12 6.628 0 12-5.373 12-12 0-6.627-5.372-12-12-12zm0 22c-5.523 0-10-4.477-10-10 0-5.523 4.477-10 10-10 5.522 0 10 4.477 10 10 0 5.523-4.478 10-10 10z"/>
                            </svg>
                            Speak to Admissions (WhatsApp)
                        </span>
                    </a>
                    <a href="#" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Book a Tour
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    @include('layouts.footer')
</body>
</html>