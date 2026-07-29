<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-lg shadow-lg flex items-center justify-center">
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Accounts Officer Dashboard
                </h2>
                <a href="{{ route('home') }}"
                    class="text-white hover:text-emerald-100 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Fees Management</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Import closing balances, review fee imports, and manage student result access.
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('accounts-officer.fees.index') }}"
                                class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700">
                                View Fees
                            </a>

                            <a href="{{ route('accounts-officer.fees.import') }}"
                                class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Import Excel
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <a href="{{ route('accounts-officer.fees.index') }}"
                            class="block rounded-lg border border-emerald-100 bg-emerald-50 p-5 hover:bg-emerald-100 transition">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-emerald-700 font-medium">Fee Balances</p>
                                    <p class="text-xs text-emerald-600 mt-1">View imported closing balances</p>
                                </div>
                                <div
                                    class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('accounts-officer.fees.import') }}"
                            class="block rounded-lg border border-indigo-100 bg-indigo-50 p-5 hover:bg-indigo-100 transition">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-indigo-700 font-medium">Import Excel</p>
                                    <p class="text-xs text-indigo-600 mt-1">Upload term fee closing balances</p>
                                </div>
                                <div
                                    class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('accounts-officer.students.index') }}"
                            class="block rounded-lg border border-red-100 bg-red-50 p-5 hover:bg-red-100 transition">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-red-700 font-medium">Results Access</p>
                                    <p class="text-xs text-red-600 mt-1">Block or unblock student results</p>
                                </div>
                                <div
                                    class="w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 11c.943 0 1.809.326 2.492.871M17 16v-1a5 5 0 00-8.528-3.536M7 16v-1a5 5 0 011.464-3.536M4 4l16 16" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
