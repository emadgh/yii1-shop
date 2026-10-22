$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
New-Item -ItemType Directory -Force public/assets/fonts,protected/runtime/placeholders | Out-Null
foreach ($weight in @('Regular','Medium','Bold')) {
 Invoke-WebRequest "https://raw.githubusercontent.com/rastikerdar/vazirmatn/v30.1.0/dist/Vazir-$weight.woff2" -OutFile "public/assets/fonts/Vazir-$weight.woff2"
}
Invoke-WebRequest 'https://raw.githubusercontent.com/rastikerdar/vazirmatn/v30.1.0/OFL.txt' -OutFile 'public/assets/fonts/Vazir-LICENSE.txt'
foreach ($index in 1..8) {
 Invoke-WebRequest "https://picsum.photos/seed/bazaarche-$index/1600/1200" -OutFile "protected/runtime/placeholders/demo-$index.jpg"
}
Write-Host 'Assets downloaded. Run tools/seed.php with PHP GD to import media and demo orders.'
