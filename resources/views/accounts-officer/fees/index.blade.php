<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Fees Management</h2>
                <p class="text-sm text-gray-500 mt-1">Import fee closing balances and review previous imports.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-accounts-officer-dashboard-link />
                <a href="{{ route('accounts-officer.fees.import') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    Import Excel
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Students with Balances</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['students_with_balances'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Outstanding Students</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['outstanding_students'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">Total Outstanding</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">P{{ number_format($stats['total_outstanding'], 2) }}
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800">Recent Fee Imports</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">File</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matched</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unmatched
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($batches as $batch)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $batch->file_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $batch->academicYear?->year_name ?? 'N/A' }} /
                                        {{ $batch->term?->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span
                                            class="px-2 py-1 rounded-full text-xs font-medium {{ $batch->status === 'imported' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ ucfirst($batch->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $batch->matched_rows }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $batch->unmatched_rows + $batch->ambiguous_rows }}</td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <a href="{{ route('accounts-officer.fees.imports.show', $batch) }}"
                                            class="text-indigo-600 hover:text-indigo-900">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-6 text-center text-sm text-gray-500">No fee
                                        imports yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4">
                    {{ $batches->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
