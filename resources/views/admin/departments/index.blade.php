<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 kw-page-header rounded-2xl shadow-sm">
            <h2 class="font-semibold text-2xl text-white leading-tight">Departments & HOD Assignments</h2>
            <p class="text-white/80 text-sm mt-1">Assign teachers to departments and mark selected teachers as HODs without changing their main role.</p>
        </div>
    </x-slot>

    <div class="py-8 kw-soft-section min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-[380px_1fr] gap-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 h-fit">
                    <h3 class="text-lg font-semibold text-gray-900">Create Department</h3>
                    <form method="POST" action="{{ route('admin.departments.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Department Name</label>
                            <input name="name" required class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Mathematics">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Code</label>
                            <input name="code" class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" placeholder="MATHS">
                            <p class="text-xs text-gray-500 mt-1">Leave blank to generate automatically.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                        </div>
                        <button class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">Create Department</button>
                    </form>
                </div>

                <div class="space-y-6">
                    @forelse ($departments as $department)
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $department->name }}</h3>
                                    <p class="text-sm text-gray-500">{{ $department->code }} @if($department->description) · {{ $department->description }} @endif</p>
                                </div>
                                <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" onsubmit="return confirm('Delete this department? Assignments will also be removed.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-2 bg-red-50 text-red-700 border border-red-100 rounded-lg text-sm font-semibold hover:bg-red-100">Delete</button>
                                </form>
                            </div>

                            <div class="p-6 grid grid-cols-1 xl:grid-cols-[1fr_340px] gap-6">
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-3">Current Assignments</h4>
                                    @if ($department->assignments->isEmpty())
                                        <p class="text-sm text-gray-500">No teachers or HODs assigned yet.</p>
                                    @else
                                        <div class="overflow-x-auto rounded-lg border border-gray-100">
                                            <table class="min-w-full divide-y divide-gray-100">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Responsibility</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Year</th>
                                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-100">
                                                    @foreach ($department->assignments->sortBy(['role_in_department', 'user.name']) as $assignment)
                                                        <tr>
                                                            <td class="px-4 py-3 text-sm text-gray-800">{{ $assignment->user?->name }}</td>
                                                            <td class="px-4 py-3 text-sm text-gray-600">{{ $roles[$assignment->role_in_department] ?? $assignment->role_in_department }}</td>
                                                            <td class="px-4 py-3 text-sm text-gray-600">{{ $assignment->academicYear?->year_name ?? 'All years' }}</td>
                                                            <td class="px-4 py-3 text-right">
                                                                <form method="POST" action="{{ route('admin.departments.assignments.destroy', [$department, $assignment]) }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button class="text-red-600 hover:text-red-800 text-sm font-semibold">Remove</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>

                                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4">
                                    <h4 class="font-semibold text-gray-900">Add Assignment</h4>
                                    <form method="POST" action="{{ route('admin.departments.assign', $department) }}" class="mt-4 space-y-3">
                                        @csrf
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Teacher / Academic Staff</label>
                                            <select name="user_id" required class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                                <option value="">Select user</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }} — {{ ucfirst($user->role) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Academic Year</label>
                                            <select name="academic_year_id" class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                                <option value="">All years</option>
                                                @foreach ($academicYears as $year)
                                                    <option value="{{ $year->id }}">{{ $year->year_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Responsibility</label>
                                            <select name="role_in_department" required class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                                @foreach ($roles as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button class="w-full px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-semibold hover:bg-gray-800">Save Assignment</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center text-gray-500">No departments created yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
