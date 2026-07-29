<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Preview Fee Import</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $batch->file_name }}</p>
            </div>
            <a href="{{ route('accounts-officer.fees.index') }}"
                class="text-sm text-indigo-600 hover:text-indigo-800">Back to Fees</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-md px-4 py-3">
                    {{ $errors->first() }}
                </div>
            @endif

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

            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-900">
                <strong>Manual matching:</strong>
                Unmatched or ambiguous rows are not imported automatically. Use the dropdown in the Manual Match column
                to select the correct student, then save the match. Only matched rows are imported when you confirm.
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Matching Preview</h3>
                    @if ($batch->status !== 'imported')
                        <form method="POST" action="{{ route('accounts-officer.fees.imports.confirm', $batch) }}"
                            onsubmit="return confirm('Import all matched rows now? Unmatched and ambiguous rows will not be imported.');">
                            @csrf
                            <button
                                class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-700">
                                Confirm Import Matched Rows
                            </button>
                        </form>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Excel Row</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Excel Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matched Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Closing Balance</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase min-w-[280px]">Manual Match</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($batch->rows as $row)
                                <tr class="@if ($row->match_status === 'matched') bg-green-50/40 @elseif($row->match_status === 'ambiguous') bg-yellow-50/40 @else bg-red-50/40 @endif">
                                    <td class="px-4 py-3 text-gray-600">{{ $row->excel_row_number }}</td>

                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $row->surname }}, {{ $row->student_names }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $row->form }}</div>
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        @if ($row->matchedStudent)
                                            <div class="font-medium">
                                                {{ $row->matchedStudent->user->name ?? 'Student #' . $row->matchedStudent->id }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $row->matchedStudent->currentClass->name ?? 'No class' }}
                                            </div>
                                        @else
                                            <span class="text-gray-400">Not matched</span>
                                        @endif

                                        @if ($row->match_status === 'ambiguous' && is_array($row->possible_student_ids) && count($row->possible_student_ids))
                                            <div class="mt-2 text-xs text-yellow-700">
                                                Possible IDs: {{ implode(', ', $row->possible_student_ids) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 font-medium">
                                        P{{ number_format((float) $row->closing_balance, 2) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span
                                            class="px-2 py-1 rounded-full text-xs font-medium
                                            @if ($row->match_status === 'matched') bg-green-100 text-green-800
                                            @elseif($row->match_status === 'ambiguous') bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800 @endif">
                                            {{ ucfirst($row->match_status) }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $row->match_notes }}
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($batch->status === 'imported')
                                            <span class="text-gray-400">Import already confirmed</span>
                                        @else
                                            <form method="POST" action="{{ route('accounts-officer.fees.import-rows.manual-match', $row) }}"
                                                class="flex flex-col gap-2">
                                                @csrf
                                                @method('PATCH')

                                                <select name="student_id"
                                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    required>
                                                    <option value="">Select student...</option>
                                                    @foreach ($students as $student)
                                                        <option value="{{ $student->id }}"
                                                            @selected((int) $row->matched_student_id === (int) $student->id)>
                                                            {{ $student->user->name ?? 'Student #' . $student->id }}
                                                            — {{ $student->currentClass->name ?? 'No class' }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <button type="submit"
                                                    class="inline-flex justify-center px-3 py-1.5 rounded-md bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                                                    Save Match
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($batch->status !== 'imported')
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3 text-sm text-yellow-900">
                    <strong>Reminder:</strong> Confirm Import will import matched rows only.
                    Unmatched and ambiguous rows remain excluded until you manually match them.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
