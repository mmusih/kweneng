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
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Results Access Control</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Block or unblock student results access based on fee status.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="border rounded-lg p-5 bg-gray-50">
                            <p class="text-sm text-gray-500">Total Students</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['totalStudents'] }}</p>
                        </div>

                        <div class="border rounded-lg p-5 bg-red-50">
                            <p class="text-sm text-gray-500">Blocked Students</p>
                            <p class="text-2xl font-bold text-red-600 mt-2">{{ $stats['blockedStudents'] }}</p>
                        </div>

                        <div class="border rounded-lg p-5 bg-green-50">
                            <p class="text-sm text-gray-500">Unblocked Students</p>
                            <p class="text-2xl font-bold text-green-600 mt-2">{{ $stats['unblockedStudents'] }}</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('accounts-officer.dashboard') }}"
                        class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Student Name</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Search by student name"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                            <select name="class_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All Classes</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}"
                                        {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All</option>
                                <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked
                                </option>
                                <option value="unblocked" {{ request('status') === 'unblocked' ? 'selected' : '' }}>
                                    Unblocked</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-3">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700">
                                Filter
                            </button>

                            <a href="{{ route('accounts-officer.dashboard') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                Reset
                            </a>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left">Student</th>
                                    <th class="px-4 py-2 text-left">Admission No</th>
                                    <th class="px-4 py-2 text-left">Class</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($students as $student)
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-gray-900">
                                            {{ $student->user->name ?? 'Unknown Student' }}
                                        </td>
                                        <td class="px-4 py-2 text-gray-700">
                                            {{ $student->admission_no ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-2 text-gray-700">
                                            {{ $student->currentClass->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-2">
                                            @if ($student->fees_blocked)
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Blocked
                                                </span>
                                            @else
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Unblocked
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">
                                            @if ($student->fees_blocked)
                                                <form
                                                    action="{{ route('accounts-officer.students.unblock', $student) }}"
                                                    method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-md bg-green-600 text-white text-xs font-semibold hover:bg-green-700">
                                                        Unblock
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('accounts-officer.students.block', $student) }}"
                                                    method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-md bg-red-600 text-white text-xs font-semibold hover:bg-red-700">
                                                        Block
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                            No students found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="mt-4">
                            {{ $students->links() }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
