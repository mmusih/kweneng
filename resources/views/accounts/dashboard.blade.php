<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Accounts Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Welcome, Accounts Officer!</h3>
                        <p class="text-gray-600">Manage financial records and billing systems.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-100 p-6 rounded-lg shadow">
                            <h4 class="text-lg font-semibold text-blue-800">Pending Payments</h4>
                            <p class="text-3xl font-bold text-blue-600">0</p>
                        </div>
                        
                        <div class="bg-green-100 p-6 rounded-lg shadow">
                            <h4 class="text-lg font-semibold text-green-800">Total Revenue</h4>
                            <p class="text-3xl font-bold text-green-600">$0</p>
                        </div>
                        
                        <div class="bg-purple-100 p-6 rounded-lg shadow">
                            <h4 class="text-lg font-semibold text-purple-800">Overdue Accounts</h4>
                            <p class="text-3xl font-bold text-purple-600">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
