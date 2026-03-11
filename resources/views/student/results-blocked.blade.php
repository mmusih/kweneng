<x-app-layout>

    <x-slot name="header">
        <div class="mt-16 p-6 bg-red-50 rounded-lg shadow-md border border-red-200">
            <h2 class="font-bold text-2xl text-red-800">
                Results Access Restricted
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center">

                    <svg class="mx-auto h-14 w-14 text-red-500 mb-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                    </svg>

                    <h3 class="text-xl font-semibold text-gray-800 mb-3">
                        Results Temporarily Unavailable
                    </h3>

                    <p class="text-gray-600 mb-4">
                        Your academic results are currently not accessible.
                    </p>

                    <p class="text-sm text-gray-500">
                        Please contact the school accounts office for assistance.
                    </p>

                </div>
            </div>

        </div>
    </div>

</x-app-layout>
