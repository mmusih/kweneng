<x-app-layout>
    <x-slot name="header"><div class="mt-16 p-5 kw-page-header rounded-lg"><h2 class="text-2xl font-semibold text-white">New Requisition</h2><p class="text-white/80 text-sm">Request stationery, supplies, equipment or repairs from inventory</p></div></x-slot>
    <div class="py-8 kw-soft-section min-h-screen"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('teacher.requisitions.store') }}" class="bg-white kw-panel p-6 space-y-5">
            @csrf
            <div class="rounded-lg bg-blue-50 border border-blue-200 text-blue-800 p-4 text-sm">
                Academic year and term are selected automatically: <strong>{{ $activeAcademicYear?->year_name ?? 'No active year' }}</strong> / <strong>{{ $activeTerm?->name ?? 'No active term' }}</strong>.
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-gray-700">Request Title</label><input name="title" value="{{ old('title') }}" required placeholder="e.g. Markers for Form 2" class="mt-1 w-full rounded-md border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700">Department / Class</label><input name="department" value="{{ old('department') }}" placeholder="e.g. Mathematics, Form 3A" class="mt-1 w-full rounded-md border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700">Priority</label><select name="priority" class="mt-1 w-full rounded-md border-gray-300">@foreach($priorities as $v=>$l)<option value="{{ $v }}" @selected(old('priority','normal')===$v)>{{ $l }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-gray-700">Needed By</label><input type="date" name="needed_by" value="{{ old('needed_by') }}" class="mt-1 w-full rounded-md border-gray-300"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">Reason / Notes</label><textarea name="reason" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('reason') }}</textarea></div>

            <div class="border rounded-xl overflow-hidden">
                <div class="p-4 bg-gray-50 flex justify-between items-center"><h3 class="font-semibold text-gray-900">Requested Items</h3><button type="button" id="add-item" class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm">Add Row</button></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" id="items-table"><thead><tr class="text-left text-gray-500"><th class="px-3 py-2">Inventory item</th><th class="px-3 py-2">Item name</th><th class="px-3 py-2">Category</th><th class="px-3 py-2">Qty</th><th class="px-3 py-2">Unit</th><th class="px-3 py-2">Notes</th></tr></thead><tbody>
                        @php($oldItems = old('items', [['inventory_item_id'=>'','item_name'=>'','category'=>'','quantity'=>1,'unit'=>'item','notes'=>'']]))
                        @foreach($oldItems as $i => $row)
                            <tr>
                                <td class="px-3 py-2"><select name="items[{{ $i }}][inventory_item_id]" class="w-44 rounded-md border-gray-300"><option value="">Not sure / new item</option>@foreach($inventoryItems as $inv)<option value="{{ $inv->id }}" @selected(($row['inventory_item_id'] ?? '') == $inv->id)>{{ $inv->name }}</option>@endforeach</select></td>
                                <td class="px-3 py-2"><input name="items[{{ $i }}][item_name]" value="{{ $row['item_name'] ?? '' }}" required class="w-48 rounded-md border-gray-300"></td>
                                <td class="px-3 py-2"><input name="items[{{ $i }}][category]" value="{{ $row['category'] ?? '' }}" class="w-36 rounded-md border-gray-300"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" min="0.01" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" required class="w-24 rounded-md border-gray-300"></td>
                                <td class="px-3 py-2"><input name="items[{{ $i }}][unit]" value="{{ $row['unit'] ?? 'item' }}" required class="w-24 rounded-md border-gray-300"></td>
                                <td class="px-3 py-2"><input name="items[{{ $i }}][notes]" value="{{ $row['notes'] ?? '' }}" class="w-48 rounded-md border-gray-300"></td>
                            </tr>
                        @endforeach
                    </tbody></table>
                </div>
            </div>
            @if($errors->any())<div class="p-4 bg-red-50 text-red-700 rounded border"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="flex justify-end gap-3"><a href="{{ route('teacher.requisitions.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Cancel</a><button class="px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold">Submit Request</button></div>
        </form>
    </div></div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('add-item');
            const tbody = document.querySelector('#items-table tbody');
            let index = tbody.children.length;
            const inventoryItems = @json($inventoryItems->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[char]);
            const inventoryOptions = inventoryItems
                .map((item) => `<option value="${item.id}">${escapeHtml(item.name)}</option>`)
                .join('');
            button.addEventListener('click', function () {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="px-3 py-2"><select name="items[${index}][inventory_item_id]" class="w-44 rounded-md border-gray-300"><option value="">Not sure / new item</option>${inventoryOptions}</select></td><td class="px-3 py-2"><input name="items[${index}][item_name]" required class="w-48 rounded-md border-gray-300"></td><td class="px-3 py-2"><input name="items[${index}][category]" class="w-36 rounded-md border-gray-300"></td><td class="px-3 py-2"><input type="number" step="0.01" min="0.01" name="items[${index}][quantity]" value="1" required class="w-24 rounded-md border-gray-300"></td><td class="px-3 py-2"><input name="items[${index}][unit]" value="item" required class="w-24 rounded-md border-gray-300"></td><td class="px-3 py-2"><input name="items[${index}][notes]" class="w-48 rounded-md border-gray-300"></td>`;
                tbody.appendChild(tr);
                index++;
            });
        });
    </script>
</x-app-layout>
