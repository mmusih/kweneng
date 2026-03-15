<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admissions - Kweneng International Secondary School</title>
    <meta name="description"
        content="Apply to Kweneng International Secondary School. View the admissions procedure, required documents, 2026 fee structure, and contact details.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
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
    @include('layouts.navigation')

    <main class="flex-grow pt-20 w-full">

        <!-- Hero Section -->
        <section class="relative w-full bg-cover bg-center py-16 md:py-24"
            style="background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80')">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-900/90 to-blue-900/80"></div>

            <div class="relative z-10 px-4">
                <div class="max-w-7xl mx-auto text-center">
                    <span
                        class="inline-block bg-white/10 text-blue-100 px-4 py-2 rounded-full text-sm font-semibold tracking-wide uppercase">
                        2026 Admissions
                    </span>

                    <h1 class="text-3xl md:text-5xl font-bold text-white mt-5 mb-4">
                        Join Kweneng International Secondary School
                    </h1>

                    <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto">
                        Apply to a school committed to academic excellence, disciplined learning, and a strong Cambridge
                        curriculum foundation.
                    </p>

                    <div class="mt-8 flex flex-wrap justify-center gap-4">
                        <a href="#apply-now"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            Start Application
                        </a>

                        <a href="#fees"
                            class="bg-white hover:bg-gray-100 text-slate-900 font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            View 2026 Fees
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Admissions Overview -->
        <section class="py-16 md:py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Admissions</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">How the admission process works
                    </h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        Our admissions process is clear and straightforward. Applicants may be interviewed and may also
                        be required to sit for an entrance examination.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
                    <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">1</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Get the Form</h3>
                        <p class="text-gray-600">Collect the application form from the school office or website.</p>
                    </div>

                    <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">2</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Prepare Documents</h3>
                        <p class="text-gray-600">Complete the form and attach all required supporting documents.</p>
                    </div>

                    <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">3</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Interview / Assessment</h3>
                        <p class="text-gray-600">Applicants may be invited for an interview and, where required, an
                            entrance examination.</p>
                    </div>

                    <div class="text-center p-6 bg-gray-50 rounded-xl hover:shadow-lg transition duration-300">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">4</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Admission Decision</h3>
                        <p class="text-gray-600">Admission is based on the school’s decision and available vacancies.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Requirements -->
        <section class="py-16 md:py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Requirements</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">What applicants must submit
                    </h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        Ensure all required documents are ready before submitting your application.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                    <div class="bg-white p-8 rounded-2xl shadow-lg">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                            <span class="bg-indigo-100 p-2 rounded-lg mr-3">📄</span>
                            Required Documents
                        </h3>

                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <span class="text-green-600 text-xl mr-3 mt-1">✓</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Completed application / registration form</h4>
                                    <p class="text-gray-600">All sections should be completed clearly and accurately.
                                    </p>
                                </div>
                            </li>

                            <li class="flex items-start">
                                <span class="text-green-600 text-xl mr-3 mt-1">✓</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Official identity document</h4>
                                    <p class="text-gray-600">A copy of a birth certificate or national ID.</p>
                                </div>
                            </li>

                            <li class="flex items-start">
                                <span class="text-green-600 text-xl mr-3 mt-1">✓</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Latest school report</h4>
                                    <p class="text-gray-600">Most recent report from the previous school.</p>
                                </div>
                            </li>

                            <li class="flex items-start">
                                <span class="text-green-600 text-xl mr-3 mt-1">✓</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Official examination results</h4>
                                    <p class="text-gray-600">Where applicable, include copies of any official exam
                                        results already taken.</p>
                                </div>
                            </li>

                            <li class="flex items-start">
                                <span class="text-green-600 text-xl mr-3 mt-1">✓</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Transfer / release letter</h4>
                                    <p class="text-gray-600">Required for students transferring from another school,
                                        especially mid-course.</p>
                                </div>
                            </li>

                            <li class="flex items-start">
                                <span class="text-green-600 text-xl mr-3 mt-1">✓</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Recent passport photo</h4>
                                    <p class="text-gray-600">One recent passport-sized photograph.</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white p-8 rounded-2xl shadow-lg">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                            <span class="bg-indigo-100 p-2 rounded-lg mr-3">🎓</span>
                            Important Admissions Notes
                        </h3>

                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <span class="text-indigo-600 text-xl mr-3 mt-1">•</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Interview may be required</h4>
                                    <p class="text-gray-600">Applicants may be invited to attend an interview at the
                                        school.</p>
                                </div>
                            </li>

                            <li class="flex items-start">
                                <span class="text-indigo-600 text-xl mr-3 mt-1">•</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Entrance examination may be required</h4>
                                    <p class="text-gray-600">Some applicants may be required to sit for an entrance
                                        assessment.</p>
                                </div>
                            </li>

                            <li class="flex items-start">
                                <span class="text-indigo-600 text-xl mr-3 mt-1">•</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Admission depends on vacancies</h4>
                                    <p class="text-gray-600">Acceptance is based on the school’s final decision and the
                                        availability of places.</p>
                                </div>
                            </li>

                            <li class="flex items-start">
                                <span class="text-indigo-600 text-xl mr-3 mt-1">•</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Transfer students need clearance</h4>
                                    <p class="text-gray-600">Students coming from another school must provide a
                                        clearance / transfer letter.</p>
                                </div>
                            </li>

                            <li class="flex items-start">
                                <span class="text-indigo-600 text-xl mr-3 mt-1">•</span>
                                <div>
                                    <h4 class="font-bold text-gray-900">Application forms are available from the school
                                    </h4>
                                    <p class="text-gray-600">Parents can obtain forms from the school office or
                                        website.
                                    </p>
                                </div>
                            </li>
                        </ul>

                        <div class="mt-8 rounded-xl bg-blue-50 border border-blue-100 p-5">
                            <h4 class="font-bold text-blue-900 mb-2">Need help with the application?</h4>
                            <p class="text-blue-800 text-sm mb-4">
                                Our admissions team can guide you on forms, requirements, and fee payments.
                            </p>
                            <a href="https://wa.me/26776855620"
                                class="inline-block bg-[#25D366] hover:bg-[#20B957] text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                                Speak to Admissions on WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Fees -->
        <section id="fees" class="py-16 md:py-20 bg-gradient-to-r from-indigo-700 to-indigo-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-200 font-semibold text-sm uppercase tracking-wider">2026 Fees</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-white mt-2 mb-4">Fee Structure</h2>
                    <p class="text-indigo-100 max-w-3xl mx-auto">
                        A clear overview of annual and discounted tuition for the 2026 academic year.
                    </p>
                </div>

                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden max-w-5xl mx-auto">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-slate-900 text-white">
                                <tr>
                                    <th class="py-4 px-6 text-left font-semibold">Form</th>
                                    <th class="py-4 px-6 text-left font-semibold">Annual Fee</th>
                                    <th class="py-4 px-6 text-left font-semibold">Annual Fee with 5% Early Payment
                                        Discount</th>
                                    <th class="py-4 px-6 text-left font-semibold">Termly x 3</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-gray-700">
                                <tr>
                                    <td class="py-4 px-6 font-semibold">Form 1</td>
                                    <td class="py-4 px-6">P 33,033.00</td>
                                    <td class="py-4 px-6">P 31,381.35</td>
                                    <td class="py-4 px-6">P 11,011.00</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="py-4 px-6 font-semibold">Form 2</td>
                                    <td class="py-4 px-6">P 33,033.00</td>
                                    <td class="py-4 px-6">P 31,381.35</td>
                                    <td class="py-4 px-6">P 11,011.00</td>
                                </tr>
                                <tr>
                                    <td class="py-4 px-6 font-semibold">Form 3</td>
                                    <td class="py-4 px-6">P 37,389.00</td>
                                    <td class="py-4 px-6">P 35,519.55</td>
                                    <td class="py-4 px-6">P 12,463.00</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="py-4 px-6 font-semibold">Form 4</td>
                                    <td class="py-4 px-6">P 37,389.00</td>
                                    <td class="py-4 px-6">P 35,519.55</td>
                                    <td class="py-4 px-6">P 12,463.00</td>
                                </tr>
                                <tr>
                                    <td class="py-4 px-6 font-semibold">Form 5</td>
                                    <td class="py-4 px-6">P 37,389.00</td>
                                    <td class="py-4 px-6">P 35,519.55</td>
                                    <td class="py-4 px-6">P 12,463.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 max-w-5xl mx-auto mt-10">
                    <div class="bg-white text-gray-800 rounded-2xl p-6 shadow-xl">
                        <div class="text-indigo-600 text-4xl mb-4">🧾</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Additional Charges</h3>
                        <ul class="text-sm space-y-3">
                            <li class="flex justify-between">
                                <span class="text-gray-600">Registration Fee</span>
                                <span class="font-semibold">P 300.00</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-gray-600">P.T.A.</span>
                                <span class="font-semibold">P 300.00</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="text-gray-600">Development Levy</span>
                                <span class="font-semibold">P 1,000.00</span>
                            </li>
                        </ul>
                        <p class="text-xs text-gray-500 mt-4">
                            Admission fees are not refundable.
                        </p>
                    </div>

                    <div class="bg-white text-gray-800 rounded-2xl p-6 shadow-xl">
                        <div class="text-indigo-600 text-4xl mb-4">🎯</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Discounts</h3>
                        <ul class="text-sm space-y-3 text-gray-600">
                            <li>• 5% discount if full payment is made before 31 January 2026</li>
                            <li>• 5% discount for a second child</li>
                            <li>• 10% discount for a third and subsequent child</li>
                            <li>• Discounts do not apply to levies</li>
                            <li>• Family discount applies to the youngest qualifying sibling only</li>
                        </ul>
                    </div>

                    <div class="bg-white text-gray-800 rounded-2xl p-6 shadow-xl">
                        <div class="text-indigo-600 text-4xl mb-4">🏦</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Payment Notes</h3>
                        <ul class="text-sm space-y-3 text-gray-600">
                            <li>• A payment agreement form must be completed each academic year</li>
                            <li>• Payments must be made on or before the first day of the month</li>
                            <li>• Payment is by bank transfer or bank deposit only</li>
                            <li>• No cash payments are allowed at school</li>
                            <li>• Proof of payment must be sent to the school</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-indigo-100 text-sm">
                        External examination fees are charged separately at the point of examination registration.
                    </p>
                </div>
            </div>
        </section>

        <!-- Why Parents Choose Us -->
        <section class="py-16 md:py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-indigo-600 font-semibold text-sm uppercase tracking-wider">Why KISS</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2 mb-4">Why families choose our school
                    </h2>
                    <p class="text-gray-600 max-w-3xl mx-auto">
                        We combine academic ambition, subject choice, and a strong learning environment to help pupils
                        excel.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-gray-50 p-8 rounded-2xl shadow-sm">
                        <div class="text-4xl mb-4">🎓</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Wide subject choice</h3>
                        <p class="text-gray-600">
                            Learners benefit from a wide choice of subjects and a curriculum designed for success.
                        </p>
                    </div>

                    <div class="bg-gray-50 p-8 rounded-2xl shadow-sm">
                        <div class="text-4xl mb-4">👩‍🏫</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Qualified teachers</h3>
                        <p class="text-gray-600">
                            Enthusiastic and qualified teachers support learners with high teaching standards and
                            regular
                            academic guidance.
                        </p>
                    </div>

                    <div class="bg-gray-50 p-8 rounded-2xl shadow-sm">
                        <div class="text-4xl mb-4">🏫</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Supportive environment</h3>
                        <p class="text-gray-600">
                            Our school promotes determination, parental support, discipline, and motivation to succeed.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section id="apply-now" class="py-16 md:py-20 bg-gray-50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Ready to apply?</h2>
                <p class="text-gray-600 text-lg mb-8 max-w-2xl mx-auto">
                    Get in touch with our admissions team for application forms, document guidance, and fee enquiries.
                </p>

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="#"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Download Application Form
                    </a>

                    <a href="https://wa.me/26776855620"
                        class="bg-[#25D366] hover:bg-[#20B957] text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                                <path
                                    d="M12 0C5.373 0 0 5.373 0 12c0 6.628 5.373 12 12 12 6.628 0 12-5.373 12-12 0-6.627-5.372-12-12-12zm0 22c-5.523 0-10-4.477-10-10 0-5.523 4.477-10 10-10 5.522 0 10 4.477 10 10 0 5.523-4.478 10-10 10z" />
                            </svg>
                            Speak to Admissions
                        </span>
                    </a>

                    <a href="{{ route('contact') }}"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Contact School
                    </a>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.footer')
</body>

</html>
