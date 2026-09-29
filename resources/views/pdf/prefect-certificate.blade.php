<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
    @include('pdf.partials.prefect-certificate-styles')
</style></head><body>
    @include('pdf.partials.prefect-certificate-content', ['pageBreakAfter' => false])
</body></html>
