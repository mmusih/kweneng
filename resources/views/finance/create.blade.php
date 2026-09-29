<x-app-layout><x-slot name="header"><h1 class="text-2xl font-bold">Receive payment</h1></x-slot><x-erp-panel>
<form method="post" action="{{ route('finance.payments.store') }}" class="kw-panel bg-white p-5 space-y-5">@csrf<input type="hidden" name="submission_key" value="{{ old('submission_key',(string) Illuminate\Support\Str::uuid()) }}">
<div class="grid md:grid-cols-2 gap-4">
@foreach(['payer_name'=>'Payer name','payer_email'=>'Receipt email','paid_on'=>'Payment date','amount'=>'Total received (BWP)','reference'=>'Bank / transaction reference'] as $field=>$label)<label>{{ $label }}<input name="{{ $field }}" value="{{ old($field,$field==='paid_on' ? date('Y-m-d') : '') }}" type="{{ $field==='paid_on' ? 'date' : ($field==='payer_email' ? 'email' : 'text') }}" @if($field==='amount') inputmode="decimal" @endif @required(in_array($field,['payer_name','paid_on','amount'])) class="block w-full rounded border-gray-300"></label>@endforeach
<label>Payment method<select name="method" class="block w-full rounded border-gray-300">@foreach(['cash','bank_transfer','card','mobile_money','cheque'] as $method)<option value="{{ $method }}" @selected(old('method')===$method)>{{ ucwords(str_replace('_',' ',$method)) }}</option>@endforeach</select></label>
<label class="md:col-span-2">Parent portal recipient (optional)<select name="parent_user_id" class="block w-full rounded border-gray-300"><option value="">No parent portal access — email / Accounts download only</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected(old('parent_user_id')==$parent->id)>{{ $parent->name }} — {{ $parent->email }}</option>@endforeach</select><span class="text-sm">Select only the parent authorised to receive the complete receipt. All listed students must belong to that parent’s linked family.</span></label>
</div>
<h2 class="font-bold text-lg">Allocate this payment</h2><p class="text-sm">Only categories marked “fees” reduce the student fee ledger. A fee ledger must be opened first. All allocations must add up to the total received.</p>
<div id="payment-items" class="space-y-4">
@foreach(old('items',[['payment_category_id'=>'','student_id'=>'','description'=>'','amount'=>'']]) as $index=>$item)
<div class="payment-item grid md:grid-cols-4 gap-3 border rounded p-3">
<label>Category<select name="items[{{ $index }}][payment_category_id]" required class="block w-full rounded border-gray-300">@foreach($categories as $category)<option value="{{ $category->id }}" @selected(($item['payment_category_id']??'')==$category->id)>{{ $category->name }} ({{ $category->kind }})</option>@endforeach</select></label>
<label>Student (optional)<select name="items[{{ $index }}][student_id]" class="block w-full rounded border-gray-300"><option value="">Not student-specific</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected(($item['student_id']??'')==$student->id)>{{ $student->user?->name }} — {{ $student->admission_no }}</option>@endforeach</select></label>
<label>Description<input name="items[{{ $index }}][description]" value="{{ $item['description']??'' }}" required maxlength="255" class="block w-full rounded border-gray-300"></label><label>Amount (BWP)<input name="items[{{ $index }}][amount]" value="{{ $item['amount']??'' }}" required inputmode="decimal" class="block w-full rounded border-gray-300"></label><button type="button" class="remove-item underline text-sm">Remove allocation</button>
</div>@endforeach</div>
<button type="button" id="add-payment-item" class="underline">Add allocation</button>
<label class="block"><input type="checkbox" name="confirm_now" value="1" @checked(old('confirm_now'))> I have verified receipt of these funds. Confirm and issue receipt now.</label>
<p class="text-sm">Leave unchecked for uncleared cheques or transfers awaiting verification. Confirmed receipts are emailed when a receipt email is supplied.</p>
<button class="rounded bg-indigo-700 text-white px-5 py-3">Save payment</button></form>
<details class="kw-panel bg-white p-5"><summary>Add payment category</summary><form action="{{ route('finance.categories.store') }}" method="post" class="flex flex-wrap gap-3 mt-3">@csrf<label>Name<input name="name" required class="block rounded border-gray-300"></label><label>Treatment<select name="kind" class="block rounded border-gray-300"><option value="income">Other income</option><option value="fees">Student fees (reduces fee ledger)</option><option value="deposit">Refundable deposit</option></select></label><button class="underline">Add category</button></form></details>
@push('scripts')<script>
document.addEventListener('DOMContentLoaded', () => {
 const container = document.getElementById('payment-items'); let next = container.children.length;
 document.getElementById('add-payment-item').addEventListener('click', () => {
  const row = container.firstElementChild.cloneNode(true);
  row.querySelectorAll('[name]').forEach(input => { input.name = input.name.replace(/items\[\d+\]/, `items[${next}]`); input.value = input.tagName === 'SELECT' ? input.options[0].value : ''; });
  next++; container.appendChild(row);
 });
 container.addEventListener('click', e => { if(e.target.classList.contains('remove-item') && container.children.length > 1) e.target.closest('.payment-item').remove(); });
});
</script>@endpush
</x-erp-panel></x-app-layout>
