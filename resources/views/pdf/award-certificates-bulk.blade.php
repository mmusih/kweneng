<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
    @include('pdf.partials.award-certificate-styles')
</style></head><body>
@foreach($awards as $award)
    @include('pdf.partials.award-certificate-content', ['pageBreakAfter' => ! $loop->last])
@endforeach
</body></html>
