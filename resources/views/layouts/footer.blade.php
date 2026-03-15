<footer class="bg-slate-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

            <!-- School Info -->
            <div class="lg:col-span-4">
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Kweneng International Logo" class="h-14 w-auto">
                    <div>
                        <h3 class="text-xl font-bold leading-tight">Kweneng International</h3>
                        <p class="text-sm text-slate-300">Secondary School</p>
                    </div>
                </div>

                <p class="text-slate-300 leading-relaxed mb-5">
                    A Cambridge curriculum school committed to academic excellence, discipline, and preparing students
                    for success in a changing world.
                </p>

                <div class="flex items-center gap-3">

                    <a href="https://facebook.com/kwenenginternational"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-slate-800 hover:bg-blue-600 transition"
                        aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>

                    <a href="https://wa.me/26777738838"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-slate-800 hover:bg-green-600 transition"
                        aria-label="WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>

                    <!-- Contact Page instead of mailto -->
                    <a href="{{ route('contact') }}"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-slate-800 hover:bg-indigo-600 transition"
                        aria-label="Contact">
                        <i class="fas fa-envelope"></i>
                    </a>

                </div>
            </div>

            <!-- Quick Links -->
            <div class="lg:col-span-2">
                <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-3 text-slate-300">
                    <li><a href="{{ route('home') }}" class="hover:text-white transition">Home</a></li>
                    <li><a href="{{ route('about') }}" class="hover:text-white transition">About</a></li>
                    <li><a href="{{ route('academics') }}" class="hover:text-white transition">Academics</a></li>
                    <li><a href="{{ route('admissions') }}" class="hover:text-white transition">Admissions</a></li>
                    <li><a href="{{ route('faq') }}" class="hover:text-white transition">FAQ</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white transition">Contact</a></li>
                    <li><a href="{{ route('alumni') }}" class="hover:text-white transition">Alumni</a></li>
                </ul>
            </div>

            <!-- Programs + Contact -->
            <div class="lg:col-span-3">
                <h4 class="text-lg font-semibold mb-4">Programs & Contact</h4>

                <ul class="space-y-3 text-slate-300 mb-6">
                    <li><a href="#" class="hover:text-white transition">Cambridge IGCSE</a></li>
                    <li><a href="{{ route('student-life') }}" class="hover:text-white transition">Student Life</a></li>
                </ul>

                <div class="space-y-2 text-slate-300 text-sm">
                    <p><span class="text-white font-medium">Address:</span> P O Box 586, Molepolole</p>
                    <p><span class="text-white font-medium">Tel:</span> 5915015 / 5915016</p>
                    <p><span class="text-white font-medium">WhatsApp:</span> 77738838</p>
                    <p><span class="text-white font-medium">Email:</span> kwenenginternational@gmail.com</p>
                    <p><span class="text-white font-medium">Email:</span> info@kwenenginternational.com</p>
                    <p><span class="text-white font-medium">Hours:</span> Mon-Fri, 7:30 AM - 4:00 PM</p>
                </div>
            </div>

            <!-- Map -->
            <div class="lg:col-span-3">
                <h4 class="text-lg font-semibold mb-4">Find Us</h4>
                <div class="rounded-2xl overflow-hidden border border-slate-700 shadow-lg bg-slate-800">
                    <iframe src="https://www.google.com/maps?q=Molepolole,Botswana&output=embed" width="100%"
                        height="260" style="border:0;" allowfullscreen="" loading="lazy">
                    </iframe>
                </div>
            </div>

        </div>

        <div class="border-t border-slate-700 mt-10 pt-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">

                <div class="text-sm text-slate-400 text-center md:text-left">
                    &copy; {{ date('Y') }} Kweneng International Secondary School. All rights reserved.
                </div>

                <!-- Cambridge logo on far right -->
                <div class="flex items-center gap-3">
                    <span class="text-xs uppercase tracking-wider text-slate-500">Cambridge Pathway</span>
                    <img src="{{ asset('images/cambridge-logo.png') }}" alt="Cambridge Logo"
                        class="h-10 w-auto bg-white rounded px-2 py-1">
                </div>

            </div>
        </div>
    </div>
</footer>
