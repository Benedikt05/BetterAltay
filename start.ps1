﻿[CmdletBinding(PositionalBinding=$false)]
param (
	[string]$php = "",
	[switch]$Loop = $true,
	[string]$file = "",
	[string][Parameter(ValueFromRemainingArguments)]$extraPocketMineArgs
)

if($php -ne ""){
	$binary = $php
}elseif(Test-Path "bin\php\php.exe"){
	$env:PHPRC = ""
	$binary = "bin\php\php.exe"
}elseif((Get-Command php -ErrorAction SilentlyContinue)){
	$binary = "php"
}else{
	echo "Couldn't find a PHP binary in system PATH or $pwd\bin\php"
	echo "Please refer to the installation instructions at https://doc.pmmp.io/en/rtfd/installation.html"
	pause
	exit 1
}

if($file -eq ""){
	if(Test-Path "Esko.phar"){
	    $file = "Esko.phar"
	}elseif (Test-Path "src\pocketmine\PocketMine.php"){
	    $file = "src\pocketmine\PocketMine.php"
	}else{
	    echo "Couldn't find EskoBE installation"
	    echo "Downloads can be found at https://github.com/MCPE357/EskoBE/releases"
	    pause
	    exit 1
	}
}

function StartServer{
	$command = "powershell -NoProfile " + $binary + " " + $file + " " + $extraPocketMineArgs
	iex $command
}

$loops = 0

StartServer

while($Loop){
	if($loops -ne 0){
		echo ("Restarted " + $loops + " times")
	}
	$loops++
	echo "To escape the loop, press CTRL+C now. Otherwise, wait 5 seconds for the server to restart."
	Start-Sleep 5
	StartServer
}