<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-lg shadow-lg">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Librarian Dashboard
            </h2>
            <p class="text-emerald-100 text-sm mt-1">
                Library catalog, loans, returns, and overdue management will appear here in later phases.
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Welcome</h3>
                <p class="text-gray-600">
                    This dashboard is reserved for future library operations.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>