<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Fee Import Details</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $batch->file_name }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-accounts-officer-dashboard-link />
                <a href="{{ route('accounts-officer.fees.index') }}"
                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Back to Fees</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3">
                    {{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Academic Year</p>
                        <p class="font-semibold">{{ $batch->academicYear?->year_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Term</p>
                        <p class="font-semibold">{{ $batch->term?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Status</p>
                        <p class="font-semibold">{{ ucfirst($batch->status) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Imported At</p>
                        <p class="font-semibold">{{ $batch->imported_at?->format('Y-m-d H:i') ?? 'Not imported' }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg border">
                    <p class="text-sm text-gray-500">Rows</p>
                    <p class="text-2xl font-bold">{{ $batch->total_rows }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg border">
                    <p class="text-sm text-gray-500">Matched</p>
                    <p class="text-2xl font-bold text-green-600">{{ $batch->matched_rows }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg border">
                    <p class="text-sm text-gray-500">Unmatched</p>
                    <p class="text-2xl font-bold text-red-600">{{ $batch->unmatched_rows }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg border">
                    <p class="text-sm text-gray-500">Ambiguous</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $batch->ambiguous_rows }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-semibold text-gray-800">Imported Rows</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Excel Row
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Excel
                                    Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matched
                                    Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Closing
                                    Balance</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($batch->rows as $row)
                                <tr>
                                    <td class="px-4 py-3 text-gray-600">{{ $row->excel_row_number }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $row->surname }},
                                            {{ $row->student_names }}</div>
                                        <div class="text-xs text-gray-500">{{ $row->form }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        @if ($row->matchedStudent)
                                            {{ $row->matchedStudent->user->name ?? 'Student #' . $row->matchedStudent->id }}
                                            <div class="text-xs text-gray-500">
                                                {{ $row->matchedStudent->currentClass->name ?? 'No class' }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        P{{ number_format((float) $row->closing_balance, 2) }}</td>
                                    <td class="px-4 py-3">{{ ucfirst($row->match_status) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
