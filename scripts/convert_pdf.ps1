$docPath = 'C:\xampp\htdocs\travel_daklak\Bao_Cao_Do_An_DakLak_Travel_AI.doc'
$pdfPath = 'C:\xampp\htdocs\travel_daklak\Bao_Cao_Do_An_DakLak_Travel_AI.pdf'

try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $doc = $word.Documents.Open($docPath)
    $doc.SaveAs([ref]$pdfPath, [ref]17)
    $doc.Close()
    $word.Quit()
    Write-Host "Da xuat thanh cong file PDF tai: $pdfPath"
} catch {
    Write-Host "Loi khi chuyen doi sang PDF: $_"
}
