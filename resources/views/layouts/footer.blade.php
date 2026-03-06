<footer class="bg-gray-800 text-white py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">Kweneng International Secondary School</h3>
                <p class="text-gray-400 mb-4">Preparing students for excellence</p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition">FB</a>
                    <a href="https://wa.me/26777738838" class="text-gray-400 hover:text-white transition">WA</a>
                </div>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="{{ route('home') }}" class="hover:text-white transition">Home</a></li>
                    <li><a href="{{ route('about') }}" class="hover:text-white transition">About</a></li>
                    <li><a href="{{ route('academics') }}" class="hover:text-white transition">Academics</a></li>
                    <li><a href="{{ route('admissions') }}" class="hover:text-white transition">Admissions</a></li>
                    <li><a href="{{ route('faq') }}" class="hover:text-white transition">FAQ</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white transition">Contact</a></li>
                    <li><a href="{{ route('alumni') }}" class="hover:text-white transition">Alumni</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Programs</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="#" class="hover:text-white transition">Cambridge IGCSE</a></li>
                    <li><a href="#" class="hover:text-white transition">AS & A Levels</a></li>
                    <li><a href="{{ route('student-life') }}" class="hover:text-white transition">Extracurricular Activities</a></li>
                    <li><a href="#" class="hover:text-white transition">Sports Program</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Contact Info</h4>
                <ul class="space-y-2 text-gray-400">
                    <li>📍 P O Box 586, Molepolole</li>
                    <li>📞 5915015 or 5915016</li>
                    <li>📱 WhatsApp: 77738838</li>
                    <li>✉️ kwenenginternational@gmail.com</li>
                    <li>✉️ info@kwenenginternational.com</li>
                    <li>🕐 Mon-Fri: 7:30 AM - 4:00 PM</li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; {{ date('Y') }} Kweneng International Secondary School. All rights reserved.</p>
        </div>
    </div>
</footer>
