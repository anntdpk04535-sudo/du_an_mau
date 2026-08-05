$word = New-Object -ComObject Word.Application
$word.Visible = $false
$doc = $word.Documents.Open('c:\xampp\htdocs\travel_daklak\baimau.pdf')
$text = $doc.Content.Text
$doc.Close()
$word.Quit()
Set-Content -Path 'c:\xampp\htdocs\travel_daklak\scripts\baimau_text.txt' -Value $text -Encoding UTF8
Write-Host "Done! Text length: "$text.Length
