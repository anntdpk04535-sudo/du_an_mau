$word = New-Object -ComObject Word.Application
$word.Visible = $false
$doc = $word.Documents.Open('C:\xampp\htdocs\travel_daklak\Bao_Cao_Do_An_DakLak_Travel_AI.doc')
$pages = $doc.ComputeStatistics(2) # 2 = wdStatisticPages
$words = $doc.ComputeStatistics(0) # 0 = wdStatisticWords
$doc.Close()
$word.Quit()

Write-Host "So trang trong Word: $pages"
Write-Host "So tu trong Word: $words"
