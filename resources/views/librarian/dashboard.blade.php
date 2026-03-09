<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-lg shadow-lg">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Librarian Dashboard
            </h2>
            <p class="text-emerald-100 text-sm mt-1">
                Library operations dashboard
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    Welcome
                </h3>
                <p class="text-gray-600">
                    The librarian role is active. Library catalog, issue/return workflows, overdue books, and borrowing history will be added in later phases.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500">Role</p>
                    <h4 class="text-xl font-bold text-gray-900 mt-2">Librarian</h4>
                    <p class="text-sm text-gray-500 mt-2">Operational library access only</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500">Academic Access</p>
                    <h4 class="text-xl font-bold text-red-600 mt-2">Restricted</h4>
                    <p class="text-sm text-gray-500 mt-2">No marks or academic structure access</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500">Module Status</p>
                    <h4 class="text-xl font-bold text-indigo-600 mt-2">Ready</h4>
                    <p class="text-sm text-gray-500 mt-2">Dashboard available for future library features</p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>