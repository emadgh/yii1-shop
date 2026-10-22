param([string]$PhpBinary=$env:SHOP_PHP_BINARY,[int]$Port=8080)
$ErrorActionPreference='Stop'
Set-Location $PSScriptRoot
if(!$PhpBinary){$command=Get-Command php -ErrorAction SilentlyContinue;if($command){$PhpBinary=$command.Source}elseif(Test-Path 'H:\wamp64\bin\php\php8.4.0\php.exe'){$PhpBinary='H:\wamp64\bin\php\php8.4.0\php.exe'}else{throw 'PHP not found. Add PHP to PATH or use -PhpBinary.'}}
$phpOptions=@()
if($PhpBinary -match 'wamp64'){$extensions=Join-Path (Split-Path $PhpBinary) 'ext';$phpOptions=@('-n','-d',"extension_dir=$extensions");foreach($extension in @('mbstring','fileinfo','pdo_sqlite','openssl','gd')){$phpOptions+=@('-d',"extension=php_$extension.dll")}}
$phpOptions+=@('-d','upload_max_filesize=10M','-d','post_max_size=12M','-d','memory_limit=256M','-d','session.save_path=protected/runtime')
if(Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue){throw "Port $Port is already in use."}
& $PhpBinary @phpOptions tools/upgrade.php
if($LASTEXITCODE -ne 0){throw 'Database upgrade failed'}
# Quote the executable options for Start-Process, which flattens ArgumentList on Windows.
$workerArguments=($phpOptions+@('tools/reservations.php','--watch') | ForEach-Object {'"'+($_ -replace '"','\"')+'"'}) -join ' '
$worker=Start-Process -FilePath $PhpBinary -ArgumentList $workerArguments -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -RedirectStandardOutput 'protected/runtime/reservations.stdout.log' -RedirectStandardError 'protected/runtime/reservations.stderr.log' -PassThru
try{Write-Host "Store: http://127.0.0.1:$Port/ | Admin: http://127.0.0.1:$Port/login";& $PhpBinary @phpOptions -S "127.0.0.1:$Port" -t public public/router.php}finally{if(!$worker.HasExited){Stop-Process -Id $worker.Id}}
