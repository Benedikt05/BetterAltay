@echo off
TITLE EskoBE server software for Minecraft: Bedrock Edition
cd /d %~dp0

set LOOP=true
set /A LOOPS=0

set PHP_BINARY=

where /q php.exe
if %ERRORLEVEL%==0 (
	set PHP_BINARY=php
)

if exist bin\php\php.exe (
	set PHPRC=""
	set PHP_BINARY=bin\php\php.exe
)

if "%PHP_BINARY%"=="" (
	echo Couldn't find a PHP binary in system PATH or %~dp0\bin\php
	echo Please refer to the installation instructions at https://doc.pmmp.io/en/rtfd/installation.html
	pause
	exit 1
)

if exist EskoBE.phar (
	set POCKETMINE_FILE=EskoBE.phar
) else (
    if exist src/pocketmine/PocketMine.php (
        set POCKETMINE_FILE=src\pocketmine\PocketMine.php
    ) else (
        echo Couldn't find EskoBE installation
    	echo Downloads can be found at https://github.com/MCPE357/EskoBE/releases
    	pause
    	exit 1
    )
)

if exist bin\mintty.exe (
	start "" bin\mintty.exe -o Columns=88 -o Rows=32 -o AllowBlinking=0 -o FontQuality=3 -o Font="Consolas" -o FontHeight=10 -o CursorType=0 -o CursorBlinks=1 -h error -t "EskoBE" -i bin/pocketmine.ico -w max %PHP_BINARY% %POCKETMINE_FILE% --enable-ansi %*
) else (
	%PHP_BINARY% %POCKETMINE_FILE% %* || pause
)

:loop
if %LOOP% equ true (
    echo Restarted %LOOPS% times.
    echo To escape the loop, press CTRL+C now. Otherwise, wait 5 seconds for the server to restart.

    timeout 5

    set /A LOOPS+=1

    goto :start
) else (
    exit 0
)

:start
%PHP_BINARY% -c bin\php %POCKETMINE_FILE% %* || pause
goto loop