<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
    @include('pdf.partials.prefect-certificate-styles')
</style></head><body>
@foreach($prefects as $prefect)
    @include('pdf.partials.prefect-certificate-content', ['pageBreakAfter' => ! $loop->last])
@endforeach
</body></html>
