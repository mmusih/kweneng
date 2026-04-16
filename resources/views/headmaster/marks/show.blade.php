<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-4 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-lg shadow-lg">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Marks Detail
                    </h2>
                    <p class="text-orange-100 text-sm mt-1">
                        {{ $meta['teacher'] }} · {{ $meta['subject'] }} · {{ $meta['class'] }} ·
                        {{ $meta['assessment'] }}
                    </p>
                </div>

                <a href="{{ route('headmaster.marks.index') }}"
                    class="inline-flex items-center text-white hover:text-orange-100 text-sm font-medium">
                    Back to Monitor
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Admission No</th>
                                <th class="px-4 py-3 text-left">Student</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($detailRows as $row)
                                <tr class="{{ $row['missing'] ? 'bg-red-50' : 'bg-white' }}">
                                    <td class="px-4 py-3">{{ $row['admission_no'] }}</td>
                                    <td
                                        class="px-4 py-3 {{ $row['missing'] ? 'text-red-700 font-semibold' : 'text-gray-900' }}">
                                        {{ $row['student_name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($row['missing'])
                                            <span
                                                class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                Missing
                                            </span>
                                        @else
                                            <span
                                                class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Entered
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        {{ $row['value'] !== null ? number_format($row['value'], 2) : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        No students found for this assignment.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
