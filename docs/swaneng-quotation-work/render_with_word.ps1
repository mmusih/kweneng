param([string]$InputDocx, [string]$OutputPdf)
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $doc = $word.Documents.Open($InputDocx, $false, $true)
    $doc.ExportAsFixedFormat($OutputPdf, 17)
    $doc.Close($false)
} finally {
    $word.Quit()
}
