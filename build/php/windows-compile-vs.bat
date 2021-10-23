@echo off

REM For future users: This file MUST have CRLF line endings. If it doesn't, lots of inexplicable undesirable strange behaviour will result.
REM Also: Don't modify this version with sed, or it will screw up your line endings.
set PHP_MAJOR_VER=7.3
set PHP_VER=%PHP_MAJOR_VER%.8
set PHP_IS_BETA="no"
set PHP_SDK_VER=2.1.10
set PATH=C:\Program Files\7-Zip;C:\Program Files (x86)\GnuWin32\bin;%PATH%
set VC_VER=vc15
set ARCH=x64
set CMAKE_TARGET=Visual Studio 15 2017 Win64
if "%PHP_DEBUG_BUILD%"=="" (
	set PHP_DEBUG_BUILD=0
)

set LIBYAML_VER=0.2.2
set PTHREAD_W32_VER=3.0.0
set LEVELDB_MCPE_VER=ea7ef8899de400fab555de8fe5cca15da3ff4489

set PHP_PTHREADS_VER=2e568b2edd0ae9a40df425f7ae77d6608e387706
set PHP_YAML_VER=2.0.4
set PHP_POCKETMINE_CHUNKUTILS_VER=master
set PHP_IGBINARY_VER=3.0.1
REM this is 1.2.8 but tags with a "v" prefix are a pain in the ass
set PHP_DS_VER=2ddef84d3e9391c37599cb716592184315e23921
set PHP_LEVELDB_VER=9bcae79f71b81a5c3ea6f67e45ae9ae9fb2775a5
set PHP_CRYPTO_VER=5f26ac91b0ba96742cc6284cd00f8db69c3788b2
set PHP_RECURSIONGUARD_VER=d6ed5da49178762ed81dc0184cd34ff4d3254720

set script_path=%~dp0
set log_file=%script_path%compile.log
echo.>"%log_file%"

set outpath="%cd%"

where git >nul 2>nul || (call :pm-echo-error "git is required" & exit 1)
where cmake >nul 2>nul || (call :pm-echo-error "cmake is required" & exit 1)
where 7z >nul 2>nul || (call :pm-echo-error "7z is required" & exit 1)

call :pm-echo "PHP Windows compiler"
call :pm-echo "Setting up environment..."

if "%PHP_DEBUG_BUILD%"=="0" (
	set OUT_PATH_REL=Release
	set PHP_HAVE_DEBUG=enable-debug-pack
	call :pm-echo "Building release binaries with debug symbols"
) else (
	set OUT_PATH_REL=Debug
	set PHP_HAVE_DEBUG=enable-debug
	call :pm-echo "Building debug binaries"
)

if "%SOURCES_PATH%"=="" (
	set SOURCES_PATH=C:\pocketmine-php-sdk
)
call :pm-echo "Using path %SOURCES_PATH% for build sources"

REM export an env var to override this if you're using something other than the community edition
if "%VS_EDITION%"=="" (
	set VS_EDITION=Community
)
call "C:\Program Files (x86)\Microsoft Visual Studio\2017\%VS_EDITION%\VC\Auxiliary\Build\vcvarsall.bat" %ARCH% >>"%log_file%" 2>&1 || call :pm-fatal-error "Error initializing Visual Studio environment"

cd "%outpath%"

if exist bin (
	call :pm-echo "Deleting old binary folder..."
	rmdir /s /q bin >>"%log_file%" 2>&1 || call :pm-fatal-error "Failed to delete old binary folder"
)

if exist "%SOURCES_PATH%" (
	call :pm-echo "Deleting old workspace..."
	rmdir /s /q "%SOURCES_PATH%" >>"%log_file%" 2>&1 || call :pm-fatal-error "Failed to delete old workspace"
)

call :pm-echo "Getting SDK..."
git clone https://github.com/OSTC/php-sdk-binary-tools.git -b php-sdk-%PHP_SDK_VER% --depth=1 -q "%SOURCES_PATH%" >>"%log_file%" 2>&1

cd "%SOURCES_PATH%"

call bin\phpsdk_setvars.bat >>"%log_file%" 2>&1

call :pm-echo "Downloading PHP source version %PHP_VER%..."
if "%PHP_IS_BETA%" == "yes" (
	git clone https://github.com/php/php-src -b php-%PHP_VER% --depth=1 -q php-src >>"%log_file%" 2>&1 || exit 1
) else (
	call :get-zip http://windows.php.net/downloads/releases/php-%PHP_VER%-src.zip >>"%log_file%" 2>&1 || call :pm-fatal-error "Failed to download PHP source"
	move php-%PHP_VER%-src php-src >>"%log_file%" 2>&1 || call :pm-fatal-error "Failed to move PHP source to target directory"
)

set DEPS_DIR_NAME=deps
set DEPS_DIR="%SOURCES_PATH%\%DEPS_DIR_NAME%"

call :pm-echo "Downloading PHP dependencies into %DEPS_DIR%..."
call bin\phpsdk_deps.bat -u -t %VC_VER% -b %PHP_MAJOR_VER% -a %ARCH% -f -d %DEPS_DIR_NAME% >>"%log_file%" 2>&1 || exit 1


call :pm-echo "Getting additional dependencies..."
cd "%DEPS_DIR%"

call :pm-echo "Downloading LibYAML version %LIBYAML_VER%..."
call :get-zip https://github.com/yaml/libyaml/archive/%LIBYAML_VER%.zip || exit 1
move libyaml-%LIBYAML_VER% libyaml >>"%log_file%" 2>&1
cd libyaml
cmake -G "%CMAKE_TARGET%" -DBUILD_SHARED_LIBS=ON . >>"%log_file%" 2>&1 || exit 1
call :pm-echo "Compiling..."
msbuild yaml.sln /p:Configuration=RelWithDebInfo /m >>"%log_file%" 2>&1 || exit 1
call :pm-echo "Copying files..."
copy RelWithDebInfo\yaml.lib "%DEPS_DIR%\lib\yaml.lib" >>"%log_file%" 2>&1 || exit 1
copy RelWithDebInfo\yaml.dll "%DEPS_DIR%\bin\yaml.dll" >>"%log_file%" 2>&1 || exit 1
copy RelWithDebInfo\yaml.pdb "%DEPS_DIR%\bin\yaml.pdb" >>"%log_file%" 2>&1 || exit 1
copy include\yaml.h "%DEPS_DIR%\include\yaml.h" >>"%log_file%" 2>&1 || exit 1

cd "%DEPS_DIR%"

call :pm-echo "Downloading pthread-w32 version %PTHREAD_W32_VER%..."
mkdir pthread-w32
cd pthread-w32
call :get-zip https://netcologne.dl.sourceforge.net/project/pthreads4w/pthreads4w-code-v%PTHREAD_W32_VER%.zip || exit 1
move pthreads4w-code-* pthreads4w-code >>"%log_file%" 2>&1
cd pthreads4w-code

call :pm-echo "Compiling..."
nmake VC >>"%log_file%" 2>&1 || exit 1

call :pm-echo "Copying files..."
copy pthread.h "%DEPS_DIR%\include\pthread.h" >>"%log_file%" 2>&1 || exit 1
copy sched.h "%DEPS_DIR%\include\sched.h" >>"%log_file%" 2>&1 || exit 1
copy semaphore.h "%DEPS_DIR%\include\semaphore.h" >>"%log_file%" 2>&1 || exit 1
copy _ptw32.h "%DEPS_DIR%\include\_ptw32.h" >>"%log_file%" 2>&1 || exit 1
copy pthreadVC3.lib "%DEPS_DIR%\lib\pthreadVC3.lib" >>"%log_file%" 2>&1 || exit 1
copy pthreadVC3.dll "%DEPS_DIR%\bin\pthreadVC3.dll" >>"%log_file%" 2>&1 || exit 1
copy pthreadVC3.pdb "%DEPS_DIR%\bin\pthreadVC3.pdb" >>"%log_file%" 2>&1 || exit 1

cd "%DEPS_DIR%"

call :pm-echo "Downloading leveldb-mcpe version %LEVELDB_MCPE_VER%..."
call :get-zip https://github.com/pmmp/leveldb-mcpe/archive/%LEVELDB_MCPE_VER%.zip || exit 1
move leveldb-mcpe-%LEVELDB_MCPE_VER% leveldb >>"%log_file%" 2>&1
cd leveldb

call :pm-echo "Compiling..."
msbuild leveldb.sln /p:Configuration=Release /p:ZlibIncludePath="%DEPS_DIR%\include" /p:ZlibLibPath="%DEPS_DIR%\lib\zlib_a.lib" /m >>"%log_file%" 2>&1 || exit 1
call :pm-echo "Copying files..."
mkdir "%DEPS_DIR%\include\leveldb" >>"%log_file%" 2>&1 || exit 1
xcopy include\leveldb %DEPS_DIR%\include\leveldb >>"%log_file%" 2>&1 || exit 1

copy x64\Release\leveldb.lib "%DEPS_DIR%\lib\leveldb.lib" >>"%log_file%" 2>&1
copy x64\Release\leveldb.dll "%DEPS_DIR%\bin\leveldb.dll" >>"%log_file%" 2>&1
copy x64\Release\leveldb.pdb "%DEPS_DIR%\bin\leveldb.pdb" >>"%log_file%" 2>&1

cd "%DEPS_DIR%"

cd ..

call :pm-echo "Getting additional PHP extensions..."
cd php-src\ext

call :get-extension-zip-from-github "pthreads"              "%PHP_PTHREADS_VER%"              "pmmp"     "pthreads"                || exit 1
call :get-extension-zip-from-github "yaml"                  "%PHP_YAML_VER%"                  "php"      "pecl-file_formats-yaml"  || exit 1
call :get-extension-zip-from-github "pocketmine_chunkutils" "%PHP_POCKETMINE_CHUNKUTILS_VER%" "dktapps"  "PocketMine-C-ChunkUtils" || exit 1
call :get-extension-zip-from-github "igbinary"              "%PHP_IGBINARY_VER%"              "igbinary" "igbinary"                || exit 1
call :get-extension-zip-from-github "ds"                    "%PHP_DS_VER%"                    "php-ds"   "ext-ds"                  || exit 1
call :get-extension-zip-from-github "leveldb"               "%PHP_LEVELDB_VER%"               "reeze"    "php-leveldb"             || exit 1
call :get-extension-zip-from-github "recursionguard"        "%PHP_RECURSIONGUARD_VER%"        "pmmp"     "ext-recursionguard"      || exit 1

call :pm-echo " - crypto: downloading %PHP_CRYPTO_VER%..."
git clone https://github.com/bukka/php-crypto.git crypto >>"%log_file%" 2>&1 || exit 1
cd crypto
git checkout %PHP_CRYPTO_VER% >>"%log_file%" 2>&1 || exit 1
git submodule update --init --recursive >>"%log_file%" 2>&1 || exit 1
cd ..

cd ..\..

:skip
cd php-src
call :pm-echo "Configuring PHP..."
call buildconf.bat >>"%log_file%" 2>&1

call configure^
 --with-mp=auto^
 --with-prefix=pocketmine-php-bin^
 --%PHP_HAVE_DEBUG%^
 --disable-all^
 --disable-cgi^
 --enable-cli^
 --enable-zts^
 --enable-bcmath^
 --enable-calendar^
 --enable-com-dotnet^
 --enable-ctype^
 --enable-ds=shared^
 --enable-filter^
 --enable-hash^
 --enable-igbinary=shared^
 --enable-json^
 --enable-mbstring^
 --disable-opcache^
 --enable-phar^
 --enable-pocketmine-chunkutils=shared^
 --enable-recursionguard=shared^
 --enable-sockets^
 --enable-tokenizer^
 --enable-zip^
 --enable-zlib^
 --with-bz2=shared^
 --with-crypto=shared^
 --with-curl^
 --with-dom^
 --with-gd=shared^
 --with-gmp^
 --with-iconv^
 --with-leveldb=shared^
 --with-libxml^
 --with-mysqli=shared^
 --with-mysqlnd^
 --with-openssl^
 --with-pcre-jit^
 --with-pthreads=shared^
 --with-sodium^
 --with-sqlite3=shared^
 --with-xml^
 --with-yaml^
 --without-readline >>"%log_file%" 2>&1 || call :pm-fatal-error "Error configuring PHP"

call :pm-echo "Compiling PHP..."
nmake >>"%log_file%" 2>&1 || call :pm-fatal-error "Error compiling PHP"

call :pm-echo "Assembling artifacts..."
nmake snap >>"%log_file%" 2>&1 || call :pm-fatal-error "Error assembling artifacts"

call :pm-echo "Removing unneeded dependency DLLs..."
REM remove ICU DLLs copied unnecessarily by nmake snap - this needs to be removed if we ever have ext/intl as a dependency
del /q "%SOURCES_PATH%\php-src\%ARCH%\Release_TS\php-%PHP_VER%\icu*.dll" 2>&1
REM remove enchant dependencies which are unnecessarily copied - this needs to be removed if we ever have ext/enchant as a dependency
del /q "%SOURCES_PATH%\php-src\%ARCH%\Release_TS\php-%PHP_VER%\glib-*.dll" 2>&1
del /q "%SOURCES_PATH%\php-src\%ARCH%\Release_TS\php-%PHP_VER%\gmodule-*.dll" 2>&1
rmdir /s /q "%SOURCES_PATH%\php-src\%ARCH%\Release_TS\php-%PHP_VER%\lib\enchant\" 2>&1

call :pm-echo "Copying artifacts..."
cd "%outpath%"
mkdir bin
move "%SOURCES_PATH%\php-src\%ARCH%\%OUT_PATH_REL%_TS\php-%PHP_VER%" bin\php
cd bin\php

set php_ini=php.ini
call :pm-echo "Generating php.ini..."
(echo ;Custom PocketMine-MP php.ini file)>"%php_ini%"
(echo memory_limit=1024M)>>"%php_ini%"
(echo display_errors=1)>>"%php_ini%"
(echo display_startup_errors=1)>>"%php_ini%"
(echo error_reporting=-1)>>"%php_ini%"
(echo zend.assertions=-1)>>"%php_ini%"
(echo phar.readonly=0)>>"%php_ini%"
(echo extension_dir=ext)>>"%php_ini%"
(echo extension=php_pthreads.dll)>>"%php_ini%"
(echo extension=php_openssl.dll)>>"%php_ini%"
(echo extension=php_pocketmine_chunkutils.dll)>>"%php_ini%"
(echo extension=php_igbinary.dll)>>"%php_ini%"
(echo extension=php_ds.dll)>>"%php_ini%"
(echo extension=php_leveldb.dll)>>"%php_ini%"
(echo extension=php_crypto.dll)>>"%php_ini%"
(echo igbinary.compact_strings=0)>>"%php_ini%"
(echo ;zend_extension=php_opcache.dll)>>"%php_ini%"
(echo ;Optional extensions, supplied for plugin use)>>"%php_ini%"
(echo extension=php_gd2.dll)>>"%php_ini%"
(echo extension=php_mysqli.dll)>>"%php_ini%"
(echo extension=php_sqlite3.dll)>>"%php_ini%"
(echo ;Optional extensions, supplied for debugging)>>"%php_ini%"
(echo extension=php_recursionguard.dll)>>"%php_ini%"
(echo recursionguard.enabled=0 ;disabled due to minor performance impact, only enable this if you need it for debugging)>>"%php_ini%"
REM TODO: more entries

cd ..\..

call :pm-echo "Downloading Microsoft Visual C++ Redistributable 2017"
wget https://aka.ms/vs/15/release/vc_redist.x64.exe --no-check-certificate -q -O vc_redist.x64.exe || exit 1

call :pm-echo "Checking PHP build works..."
bin\php\php.exe --version >>"%log_file%" 2>&1 || call :pm-fatal-error "PHP build isn't working"

call :pm-echo "Packaging build..."
set package_filename=php-%PHP_VER%-%VC_VER%-%ARCH%.zip
if exist %package_filename% rm %package_filename%
7z a -bd %package_filename% bin vc_redist.x64.exe >nul || call :pm-fatal-error "Failed to package the build"

call :pm-echo "Created build package %package_filename%"
call :pm-echo "Moving debugging symbols to output directory..."
move "%SOURCES_PATH%\php-src\%ARCH%\%OUT_PATH_REL%_TS\php-debug-pack*.zip" .
call :pm-echo "Done?"

exit 0

:get-extension-zip-from-github:
call :pm-echo " - %~1: downloading %~2..."
call :get-zip https://github.com/%~3/%~4/archive/%~2.zip || exit /B 1
move %~4-%~2 %~1 >>"%log_file%" 2>&1 || exit /B 1
exit /B 0


:get-zip
wget %~1 --no-check-certificate -q -O temp.zip || exit /B 1
7z x -y temp.zip >nul || exit /B 1
rm temp.zip
exit /B 0

:pm-fatal-error
call :pm-echo-error "%~1 - check compile.log for details"
exit 1

:pm-echo-error
call :pm-echo "[ERROR] %~1"
exit /B 0

:pm-echo
echo [PocketMine] %~1
echo [PocketMine] %~1 >>"%log_file%" 2>&1
exit /B 0
