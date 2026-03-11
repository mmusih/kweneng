<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-4 bg-gradient-to-r from-red-500 to-pink-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Accounts Officer Dashboard
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500">Total Students</h3>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['totalStudents'] }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500">Blocked from Results</h3>
                    <p class="mt-2 text-3xl font-bold text-red-600">{{ $stats['blockedStudents'] }}</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-500">Results Access Allowed</h3>
                    <p class="mt-2 text-3xl font-bold text-green-600">{{ $stats['unblockedStudents'] }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>

                <a href="{{ route('accounts-officer.students.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md font-semibold hover:bg-red-700">
                    Manage Results Blocking
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
