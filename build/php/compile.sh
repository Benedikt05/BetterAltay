#!/bin/bash
[ -z "$PHP_VERSION" ] && PHP_VERSION="7.3.8"

PHP_IS_BETA="no"

ZLIB_VERSION="1.2.11"
GMP_VERSION="6.1.2"
CURL_VERSION="curl-7_65_3"
READLINE_VERSION="6.3"
NCURSES_VERSION="6.0"
YAML_VERSION="0.2.2"
LEVELDB_VERSION="10f59b56bec1db3ffe42ff265afe22182073e0e2"
LIBXML_VERSION="2.9.1"
LIBPNG_VERSION="1.6.37"
LIBJPEG_VERSION="9c"
OPENSSL_VERSION="1.1.0k" #1.1.1a has some segfault issues
LIBZIP_VERSION="1.5.2"

EXT_NCURSES_VERSION="1.0.2"
EXT_PTHREADS_VERSION="2e568b2edd0ae9a40df425f7ae77d6608e387706"
EXT_YAML_VERSION="2.0.4"
EXT_LEVELDB_VERSION="9bcae79f71b81a5c3ea6f67e45ae9ae9fb2775a5"
EXT_POCKETMINE_CHUNKUTILS_VERSION="master"
EXT_XDEBUG_VERSION="2.7.2"
EXT_IGBINARY_VERSION="3.0.1"
EXT_DS_VERSION="2ddef84d3e9391c37599cb716592184315e23921"
EXT_CRYPTO_VERSION="5f26ac91b0ba96742cc6284cd00f8db69c3788b2"
EXT_RECURSIONGUARD_VERSION="d6ed5da49178762ed81dc0184cd34ff4d3254720"

function write_out {
	echo "[$1] $2"
}

function write_error {
	write_out ERROR "$1" >&2
}

echo "[PocketMine] PHP compiler for Linux, MacOS and Android"
DIR="$(pwd)"
date > "$DIR/install.log" 2>&1

uname -a >> "$DIR/install.log" 2>&1
echo "[INFO] Checking dependencies"

COMPILE_SH_DEPENDENCIES=( make autoconf automake m4 getconf gzip bzip2 bison g++ git cmake )
ERRORS=0
for(( i=0; i<${#COMPILE_SH_DEPENDENCIES[@]}; i++ ))
do
	type "${COMPILE_SH_DEPENDENCIES[$i]}" >> "$DIR/install.log" 2>&1 || { write_error "Please install \"${COMPILE_SH_DEPENDENCIES[$i]}\""; ((ERRORS++)); }
done

type wget >> "$DIR/install.log" 2>&1 || type curl >> "$DIR/install.log" || { echo >&2 "[ERROR] Please install \"wget\" or \"curl\""; ((ERRORS++)); }

if [ "$(uname -s)" == "Darwin" ]; then
	type glibtool >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install GNU libtool"; ((ERRORS++)); }
	export LIBTOOL=glibtool
	export LIBTOOLIZE=glibtoolize
else
	type libtool >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install \"libtool\" or \"libtool-bin\""; ((ERRORS++)); }
	export LIBTOOL=libtool
	export LIBTOOLIZE=libtoolize
fi

if [ $ERRORS -ne 0 ]; then
	read -p "Press [Enter] to continue..."
	exit 1
fi

#Needed to use aliases
shopt -s expand_aliases
type wget >> "$DIR/install.log" 2>&1
if [ $? -eq 0 ]; then
	alias download_file="wget --no-check-certificate -q -O -"
else
	type curl >> "$DIR/install.log" 2>&1
	if [ $? -eq 0 ]; then
		alias download_file="curl --insecure --silent --show-error --location --globoff"
	else
		echo "error, curl or wget not found"
		exit 1
	fi
fi

#if type llvm-gcc >/dev/null 2>&1; then
#	export CC="llvm-gcc"
#	export CXX="llvm-g++"
#	export AR="llvm-ar"
#	export AS="llvm-as"
#	export RANLIB=llvm-ranlib
#else
	export CC="gcc"
	export CXX="g++"
	#export AR="gcc-ar"
	export RANLIB=ranlib
#fi

COMPILE_FOR_ANDROID=no
HAVE_MYSQLI="--enable-embedded-mysqli --enable-mysqlnd --with-mysqli=mysqlnd"
COMPILE_TARGET=""
COMPILE_FANCY="no"
IS_CROSSCOMPILE="no"
IS_WINDOWS="no"
DO_OPTIMIZE="no"
OPTIMIZE_TARGET=""
DO_STATIC="no"
DO_CLEANUP="yes"
COMPILE_DEBUG="no"
COMPILE_LEVELDB="no"
HAVE_VALGRIND="--without-valgrind"
FLAGS_LTO=""

LD_PRELOAD=""

COMPILE_POCKETMINE_CHUNKUTILS="no"
COMPILE_GD="no"

while getopts "::t:oj:srdlxzff:ugnv" OPTION; do

	case $OPTION in
		t)
			echo "[opt] Set target to $OPTARG"
			COMPILE_TARGET="$OPTARG"
			;;
		j)
			echo "[opt] Set make threads to $OPTARG"
			THREADS="$OPTARG"
			;;
		r)
			echo "[opt] Will compile readline and ncurses"
			COMPILE_FANCY="yes"
			;;
		d)
			echo "[opt] Will compile profiler and xdebug, will not remove sources"
			COMPILE_DEBUG="yes"
			DO_CLEANUP="no"
			CFLAGS="$CFLAGS -g"
			CXXFLAGS="$CXXFLAGS -g"
			;;
		x)
			echo "[opt] Doing cross-compile"
			IS_CROSSCOMPILE="yes"
			;;
		l)
			echo "[opt] Will compile with LevelDB support"
			COMPILE_LEVELDB="yes"
			;;
		s)
			echo "[opt] Will compile everything statically"
			DO_STATIC="yes"
			CFLAGS="$CFLAGS -static"
			;;
		f)
			echo "[opt] Enabling abusive optimizations..."
			DO_OPTIMIZE="yes"
			OPTIMIZE_TARGET="$OPTARG"
			;;
		u)
			echo "[opt] Will compile with PocketMine-ChunkUtils C extension for Anvil"
			COMPILE_POCKETMINE_CHUNKUTILS="yes"
			;;
		g)
			echo "[opt] Will enable GD2"
			COMPILE_GD="yes"
			;;
		n)
			echo "[opt] Will not remove sources after completing compilation"
			DO_CLEANUP="no"
			;;
		v)
			echo "[opt] Will enable valgrind support in PHP"
			HAVE_VALGRIND="--with-valgrind"
			;;
		\?)
			echo "Invalid option: -$OPTION$OPTARG" >&2
			exit 1
			;;
	esac
done

GMP_ABI=""
TOOLCHAIN_PREFIX=""
OPENSSL_TARGET=""

if [ "$IS_CROSSCOMPILE" == "yes" ]; then
	export CROSS_COMPILER="$PATH"
	if [[ "$COMPILE_TARGET" == "win" ]] || [[ "$COMPILE_TARGET" == "win64" ]]; then
		TOOLCHAIN_PREFIX="x86_64-w64-mingw32"
		[ -z "$march" ] && march=x86_64;
		[ -z "$mtune" ] && mtune=nocona;
		CFLAGS="$CFLAGS -mconsole"
		CONFIGURE_FLAGS="--host=$TOOLCHAIN_PREFIX --target=$TOOLCHAIN_PREFIX --build=$TOOLCHAIN_PREFIX"
		IS_WINDOWS="yes"
		OPENSSL_TARGET="mingw64"
		GMP_ABI="64"
		echo "[INFO] Cross-compiling for Windows 64-bit"
	elif [ "$COMPILE_TARGET" == "mac" ]; then
		[ -z "$march" ] && march=prescott;
		[ -z "$mtune" ] && mtune=generic;
		CFLAGS="$CFLAGS -fomit-frame-pointer";
		TOOLCHAIN_PREFIX="i686-apple-darwin10"
		CONFIGURE_FLAGS="--host=$TOOLCHAIN_PREFIX"
		#zlib doesn't use the correct ranlib
		RANLIB=$TOOLCHAIN_PREFIX-ranlib
		CFLAGS="$CFLAGS -Qunused-arguments -Wno-error=unused-command-line-argument-hard-error-in-future"
		ARCHFLAGS="-Wno-error=unused-command-line-argument-hard-error-in-future"
		OPENSSL_TARGET="darwin64-x86_64-cc"
		GMP_ABI="32"
		echo "[INFO] Cross-compiling for Intel MacOS"
	elif [ "$COMPILE_TARGET" == "android-aarch64" ]; then
		COMPILE_FOR_ANDROID=yes
		[ -z "$march" ] && march="armv8-a";
		[ -z "$mtune" ] && mtune=generic;
		TOOLCHAIN_PREFIX="aarch64-linux-musl"
		CONFIGURE_FLAGS="--host=$TOOLCHAIN_PREFIX"
		CFLAGS="-static $CFLAGS"
		CXXFLAGS="-static $CXXFLAGS"
		LDFLAGS="-static -static-libgcc -Wl,-static"
		OPENSSL_TARGET="linux-aarch64"
		echo "[INFO] Cross-compiling for Android ARMv8 (aarch64)"
	#TODO: add cross-compile for aarch64 platforms (ios, rpi)
	else
		echo "Please supply a proper platform [mac win win64 android-aarch64] to cross-compile"
		exit 1
	fi
else
	if [[ "$COMPILE_TARGET" == "" ]] && [[ "$(uname -s)" == "Darwin" ]]; then
		COMPILE_TARGET="mac"
	fi
	if [[ "$COMPILE_TARGET" == "linux" ]] || [[ "$COMPILE_TARGET" == "linux64" ]]; then
		[ -z "$march" ] && march=x86-64;
		[ -z "$mtune" ] && mtune=nocona;
		CFLAGS="$CFLAGS -m64"
		GMP_ABI="64"
		OPENSSL_TARGET="linux-x86_64"
		echo "[INFO] Compiling for Linux x86_64"
	elif [[ "$COMPILE_TARGET" == "mac" ]] || [[ "$COMPILE_TARGET" == "mac64" ]]; then
		[ -z "$march" ] && march=core2;
		[ -z "$mtune" ] && mtune=generic;
		[ -z "$MACOSX_DEPLOYMENT_TARGET" ] && export MACOSX_DEPLOYMENT_TARGET=10.9;
		CFLAGS="$CFLAGS -m64 -arch x86_64 -fomit-frame-pointer -mmacosx-version-min=$MACOSX_DEPLOYMENT_TARGET"
		LDFLAGS="$LDFLAGS -mmacosx-version-min=$MACOSX_DEPLOYMENT_TARGET"
		if [ "$DO_STATIC" == "no" ]; then
			LDFLAGS="$LDFLAGS -Wl,-rpath,@loader_path/../lib";
			export DYLD_LIBRARY_PATH="@loader_path/../lib"
		fi
		CFLAGS="$CFLAGS -Qunused-arguments -Wno-error=unused-command-line-argument-hard-error-in-future"
		ARCHFLAGS="-Wno-error=unused-command-line-argument-hard-error-in-future"
		GMP_ABI="64"
		OPENSSL_TARGET="darwin64-x86_64-cc"
		echo "[INFO] Compiling for Intel MacOS x86_64"
	#TODO: add aarch64 platforms (ios, android, rpi)
	elif [ -z "$CFLAGS" ]; then
		if [ `getconf LONG_BIT` == "64" ]; then
			echo "[INFO] Compiling for current machine using 64-bit"
			if [ "$(uname -m)" != "aarch64" ]; then
				CFLAGS="-m64 $CFLAGS"
			fi
			GMP_ABI="64"
		else
			echo "[ERROR] PocketMine-MP is no longer supported on 32-bit systems"
			exit 1
		fi
	fi
fi

if [ "$DO_OPTIMIZE" != "no" ]; then
	#FLAGS_LTO="-fvisibility=hidden -flto"
	ffast_math="-fno-math-errno -funsafe-math-optimizations -fno-signed-zeros -fno-trapping-math -ffinite-math-only -fno-rounding-math -fno-signaling-nans" #workaround SQLite3 fail
	CFLAGS="$CFLAGS -O2 -DSQLITE_HAVE_ISNAN $ffast_math -ftree-vectorize -fomit-frame-pointer -funswitch-loops -fivopts"
	if [ "$COMPILE_TARGET" != "mac" ] && [ "$COMPILE_TARGET" != "mac32" ] && [ "$COMPILE_TARGET" != "mac64" ]; then
		CFLAGS="$CFLAGS -funsafe-loop-optimizations -fpredictive-commoning -ftracer -ftree-loop-im -frename-registers -fcx-limited-range"
	fi

	if [ "$OPTIMIZE_TARGET" == "arm" ]; then
		CFLAGS="$CFLAGS -mfpu=vfp"
	elif [ "$OPTIMIZE_TARGET" == "x86_64" ]; then
		CFLAGS="$CFLAGS -mmmx -msse -msse2 -msse3 -mfpmath=sse -free -msahf -ftree-parallelize-loops=4"
	elif [ "$OPTIMIZE_TARGET" == "x86" ]; then
		CFLAGS="$CFLAGS -mmmx -msse -msse2 -mfpmath=sse -m128bit-long-double -malign-double -ftree-parallelize-loops=4"
	fi
fi

if [ "$TOOLCHAIN_PREFIX" != "" ]; then
		export CC="$TOOLCHAIN_PREFIX-gcc"
		export CXX="$TOOLCHAIN_PREFIX-g++"
		export AR="$TOOLCHAIN_PREFIX-ar"
		export RANLIB="$TOOLCHAIN_PREFIX-ranlib"
		export CPP="$TOOLCHAIN_PREFIX-cpp"
		export LD="$TOOLCHAIN_PREFIX-ld"
fi

echo "#include <stdio.h>" > test.c
echo "int main(void){" >> test.c
echo "printf(\"Hello world\n\");" >> test.c
echo "return 0;" >> test.c
echo "}" >> test.c


type $CC >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install \"$CC\""; read -p "Press [Enter] to continue..."; exit 1; }

[ -z "$THREADS" ] && THREADS=1;
[ -z "$march" ] && march=native;
[ -z "$mtune" ] && mtune=native;
[ -z "$CFLAGS" ] && CFLAGS="";

if [ "$DO_STATIC" == "no" ]; then
	[ -z "$LDFLAGS" ] && LDFLAGS="-Wl,-rpath='\$\$ORIGIN/../lib' -Wl,-rpath-link='\$\$ORIGIN/../lib'";
fi

[ -z "$CONFIGURE_FLAGS" ] && CONFIGURE_FLAGS="";

if [ "$mtune" != "none" ]; then
	$CC -march=$march -mtune=$mtune $CFLAGS -o test test.c >> "$DIR/install.log" 2>&1
	if [ $? -eq 0 ]; then
		CFLAGS="-march=$march -mtune=$mtune -fno-gcse $CFLAGS"
	fi
else
	$CC -march=$march $CFLAGS -o test test.c >> "$DIR/install.log" 2>&1
	if [ $? -eq 0 ]; then
		CFLAGS="-march=$march -fno-gcse $CFLAGS"
	fi
fi

rm test.* >> "$DIR/install.log" 2>&1
rm test >> "$DIR/install.log" 2>&1

export CC="$CC"
export CXX="$CXX"
export CFLAGS="-O2 -fPIC $CFLAGS"
export CXXFLAGS="$CFLAGS $CXXFLAGS"
export LDFLAGS="$LDFLAGS"
export CPPFLAGS="$CPPFLAGS"
export LIBRARY_PATH="$DIR/bin/php7/lib:$LIBRARY_PATH"

rm -r -f install_data/ >> "$DIR/install.log" 2>&1
rm -r -f bin/ >> "$DIR/install.log" 2>&1
mkdir -m 0755 install_data >> "$DIR/install.log" 2>&1
mkdir -m 0755 bin >> "$DIR/install.log" 2>&1
mkdir -m 0755 bin/php7 >> "$DIR/install.log" 2>&1
cd install_data
set -e

#PHP 7
echo -n "[PHP] downloading $PHP_VERSION..."

if [[ "$PHP_IS_BETA" == "yes" ]]; then
	download_file "https://github.com/php/php-src/archive/php-$PHP_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
	mv php-src-php-$PHP_VERSION php
else
	download_file "http://php.net/get/php-$PHP_VERSION.tar.gz/from/this/mirror" | tar -zx >> "$DIR/install.log" 2>&1
	mv php-$PHP_VERSION php
fi

echo " done!"

if [ "$COMPILE_FANCY" == "yes" ]; then
	if [ "$DO_STATIC" == "yes" ]; then
		EXTRA_FLAGS="--without-shared --with-static"
	else
		EXTRA_FLAGS="--with-shared --without-static"
	fi
	#ncurses
	echo -n "[ncurses] downloading $NCURSES_VERSION..."
	download_file "http://ftp.gnu.org/gnu/ncurses/ncurses-$NCURSES_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
	mv ncurses-$NCURSES_VERSION ncurses
	echo -n " checking..."
	cd ncurses
	./configure --prefix="$DIR/bin/php7" \
	--without-ada \
	--without-manpages \
	--without-progs \
	--without-tests \
	--with-normal \
	--with-pthread \
	--without-debug \
	$EXTRA_FLAGS \
	$CONFIGURE_FLAGS >> "$DIR/install.log" 2>&1
	echo -n " compiling..."
	make -j $THREADS >> "$DIR/install.log" 2>&1
	echo -n " installing..."
	make install >> "$DIR/install.log" 2>&1
	cd ..
	echo " done!"
	HAVE_NCURSES="--with-ncurses=$DIR/bin/php7"

	if [ "$DO_STATIC" == "yes" ]; then
		EXTRA_FLAGS="--enable-shared=no --enable-static=yes"
	else
		EXTRA_FLAGS="--enable-shared=yes --enable-static=no"
	fi
	#readline
	set +e
	echo -n "[readline] downloading $READLINE_VERSION..."
	download_file "http://ftp.gnu.org/gnu/readline/readline-$READLINE_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
	mv readline-$READLINE_VERSION readline
	echo -n " checking..."
	cd readline
	./configure --prefix="$DIR/bin/php7" \
	--with-curses="$DIR/bin/php7" \
	--enable-multibyte \
	$EXTRA_FLAGS \
	$CONFIGURE_FLAGS >> "$DIR/install.log" 2>&1
	echo -n " compiling..."
	if make -j $THREADS >> "$DIR/install.log" 2>&1; then
		echo -n " installing..."
		make install >> "$DIR/install.log" 2>&1
		HAVE_READLINE="--with-readline=$DIR/bin/php7"
	else
		echo -n " disabling..."
		HAVE_READLINE="--without-readline"
	fi
	cd ..
	echo " done!"
	set -e
else
	HAVE_NCURSES="--without-ncurses"
	HAVE_READLINE="--without-readline"
fi

if [ "$DO_STATIC" == "yes" ]; then
	EXTRA_FLAGS="--static"
else
	EXTRA_FLAGS="--shared"
fi

#zlib
echo -n "[zlib] downloading $ZLIB_VERSION..."
download_file "https://github.com/madler/zlib/archive/v$ZLIB_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
mv zlib-$ZLIB_VERSION zlib
echo -n " checking..."
cd zlib
RANLIB=$RANLIB ./configure --prefix="$DIR/bin/php7" \
$EXTRA_FLAGS >> "$DIR/install.log" 2>&1
echo -n " compiling..."
make -j $THREADS >> "$DIR/install.log" 2>&1
echo -n " installing..."
make install >> "$DIR/install.log" 2>&1
cd ..
	if [ "$DO_STATIC" != "yes" ]; then
		rm -f "$DIR/bin/php7/lib/libz.a"
	fi
echo " done!"

export jm_cv_func_working_malloc=yes
export ac_cv_func_malloc_0_nonnull=yes
export jm_cv_func_working_realloc=yes
export ac_cv_func_realloc_0_nonnull=yes

if [ "$IS_CROSSCOMPILE" == "yes" ]; then
	EXTRA_FLAGS=""
else
	EXTRA_FLAGS="--disable-assembly"
fi

#GMP
echo -n "[GMP] downloading $GMP_VERSION..."
download_file "https://gmplib.org/download/gmp/gmp-$GMP_VERSION.tar.bz2" | tar -jx >> "$DIR/install.log" 2>&1
mv gmp-$GMP_VERSION gmp
echo -n " checking..."
cd gmp
RANLIB=$RANLIB ./configure --prefix="$DIR/bin/php7" \
$EXTRA_FLAGS \
--disable-posix-threads \
--enable-static \
--disable-shared \
$CONFIGURE_FLAGS ABI="$GMP_ABI" >> "$DIR/install.log" 2>&1
echo -n " compiling..."
make -j $THREADS >> "$DIR/install.log" 2>&1
echo -n " installing..."
make install >> "$DIR/install.log" 2>&1
cd ..
echo " done!"


#OpenSSL
OPENSSL_CMD="./config"
if [ "$OPENSSL_TARGET" != "" ]; then
	OPENSSL_CMD="./Configure $OPENSSL_TARGET"
fi

export PKG_CONFIG_PATH="$DIR/bin/php7/lib/pkgconfig"
WITH_OPENSSL="--with-openssl=$DIR/bin/php7"
echo -n "[OpenSSL] downloading $OPENSSL_VERSION..."
download_file "http://www.openssl.org/source/openssl-$OPENSSL_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
mv openssl-$OPENSSL_VERSION openssl

echo -n " checking..."
cd openssl
RANLIB=$RANLIB $OPENSSL_CMD \
--prefix="$DIR/bin/php7" \
--openssldir="$DIR/bin/php7" \
no-asm \
no-hw \
no-shared \
no-threads \
no-engine >> "$DIR/install.log" 2>&1

echo -n " compiling..."
make -j $THREADS >> "$DIR/install.log" 2>&1
echo -n " installing..."
make install_sw >> "$DIR/install.log" 2>&1
cd ..
echo " done!"



if [ "$DO_STATIC" == "yes" ]; then
	EXTRA_FLAGS="--enable-static --disable-shared"
else
	EXTRA_FLAGS="--disable-static --enable-shared"
fi

#curl
echo -n "[cURL] downloading $CURL_VERSION..."
download_file "https://github.com/curl/curl/archive/$CURL_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
mv curl-$CURL_VERSION curl
echo -n " checking..."
cd curl
./buildconf --force >> "$DIR/install.log" 2>&1
RANLIB=$RANLIB ./configure --disable-dependency-tracking \
--enable-ipv6 \
--enable-optimize \
--enable-http \
--enable-ftp \
--disable-dict \
--enable-file \
--without-librtmp \
--disable-gopher \
--disable-imap \
--disable-pop3 \
--disable-rtsp \
--disable-smtp \
--disable-telnet \
--disable-tftp \
--disable-ldap \
--disable-ldaps \
--without-libidn \
--without-libidn2 \
--with-zlib="$DIR/bin/php7" \
--with-ssl="$DIR/bin/php7" \
--enable-threaded-resolver \
--prefix="$DIR/bin/php7" \
$EXTRA_FLAGS \
$CONFIGURE_FLAGS >> "$DIR/install.log" 2>&1
echo -n " compiling..."
make -j $THREADS >> "$DIR/install.log" 2>&1
echo -n " installing..."
make install >> "$DIR/install.log" 2>&1
cd ..
echo " done!"


if [ "$DO_STATIC" == "yes" ]; then
	EXTRA_FLAGS="--disable-shared --enable-static"
else
	EXTRA_FLAGS="--enable-shared --disable-static"
fi
#YAML
echo -n "[YAML] downloading $YAML_VERSION..."
download_file "https://github.com/yaml/libyaml/archive/$YAML_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
mv libyaml-$YAML_VERSION yaml
cd yaml
./bootstrap >> "$DIR/install.log" 2>&1

echo -n " checking..."

RANLIB=$RANLIB ./configure \
--prefix="$DIR/bin/php7" \
$EXTRA_FLAGS \
$CONFIGURE_FLAGS >> "$DIR/install.log" 2>&1
sed -i=".backup" 's/ tests win32/ win32/g' Makefile
echo -n " compiling..."
make -j $THREADS all >> "$DIR/install.log" 2>&1
echo -n " installing..."
make install >> "$DIR/install.log" 2>&1
cd ..
echo " done!"

if [ "$COMPILE_LEVELDB" == "yes" ]; then
	#LevelDB
	echo -n "[LevelDB] downloading $LEVELDB_VERSION..."
	download_file "https://github.com/pmmp/leveldb-mcpe/archive/$LEVELDB_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
	#download_file "https://github.com/Mojang/leveldb-mcpe/archive/$LEVELDB_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
	mv leveldb-mcpe-$LEVELDB_VERSION leveldb
	echo -n " checking..."
	cd leveldb
	echo -n " compiling..."
	if [ "$DO_STATIC" == "yes" ]; then
		LEVELDB_TARGET="staticlibs"
	else
		LEVELDB_TARGET="sharedlibs"
	fi
	INSTALL_PATH="$DIR/bin/php7/lib" CFLAGS="$CFLAGS -I$DIR/bin/php7/include" CXXFLAGS="$CXXFLAGS -I$DIR/bin/php7/include" LDFLAGS="$LDFLAGS -L$DIR/bin/php7/lib" make $LEVELDB_TARGET -j $THREADS >> "$DIR/install.log" 2>&1
	echo -n " installing..."
	if [ "$DO_STATIC" == "yes" ]; then
		cp out-static/lib*.a "$DIR/bin/php7/lib/"
	else
		cp out-shared/libleveldb.* "$DIR/bin/php7/lib/"
	fi
	cp -r include/leveldb "$DIR/bin/php7/include/leveldb"
	cd ..
	echo " done!"
fi

if [ "$DO_STATIC" == "yes" ]; then
	EXTRA_FLAGS="--enable-shared=no --enable-static=yes"
else
	EXTRA_FLAGS="--enable-shared=yes --enable-static=no"
fi

if [ "$COMPILE_GD" == "yes" ]; then
	#libpng
	echo -n "[libpng] downloading $LIBPNG_VERSION..."
	download_file "https://sourceforge.net/projects/libpng/files/libpng16/$LIBPNG_VERSION/libpng-$LIBPNG_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
	mv libpng-$LIBPNG_VERSION libpng
	echo -n " checking..."
	cd libpng
	LDFLAGS="$LDFLAGS -L${DIR}/bin/php7/lib" CPPFLAGS="$CPPFLAGS -I${DIR}/bin/php7/include" RANLIB=$RANLIB ./configure \
	--prefix="$DIR/bin/php7" \
	$EXTRA_FLAGS \
	$CONFIGURE_FLAGS >> "$DIR/install.log" 2>&1
	echo -n " compiling..."
	make -j $THREADS >> "$DIR/install.log" 2>&1
	echo -n " installing..."
	make install >> "$DIR/install.log" 2>&1
	cd ..
	echo " done!"

	#libjpeg
	echo -n "[libjpeg] downloading $LIBJPEG_VERSION..."
	download_file "http://ijg.org/files/jpegsrc.v$LIBJPEG_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
	mv jpeg-$LIBJPEG_VERSION libjpeg
	echo -n " checking..."
	cd libjpeg
	LDFLAGS="$LDFLAGS -L${DIR}/bin/php7/lib" CPPFLAGS="$CPPFLAGS -I${DIR}/bin/php7/include" RANLIB=$RANLIB ./configure \
	--prefix="$DIR/bin/php7" \
	$EXTRA_FLAGS \
	$CONFIGURE_FLAGS >> "$DIR/install.log" 2>&1
	echo -n " compiling..."
	make -j $THREADS >> "$DIR/install.log" 2>&1
	echo -n " installing..."
	make install >> "$DIR/install.log" 2>&1
	cd ..
	echo " done!"
	HAS_GD="--with-gd"
	HAS_LIBPNG="--with-png-dir=${DIR}/bin/php7"
	HAS_LIBJPEG="--with-jpeg-dir=${DIR}/bin/php7"
else
	HAS_GD=""
	HAS_LIBPNG=""
	HAS_LIBJPEG=""
fi

#libxml2
#echo -n "[libxml2] downloading $LIBXML_VERSION..."
#download_file "ftp://xmlsoft.org/libxml2/libxml2-$LIBXML_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
#mv libxml2-$LIBXML_VERSION libxml2
#echo -n " checking..."
#cd libxml2
#RANLIB=$RANLIB ./configure \
#--disable-ipv6 \
#--with-libz="$DIR/bin/php7" \
#--prefix="$DIR/bin/php7" \
#$EXTRA_FLAGS \
#$CONFIGURE_FLAGS >> "$DIR/install.log" 2>&1
#echo -n " compiling..."
#make -j $THREADS >> "$DIR/install.log" 2>&1
#echo -n " installing..."
#make install >> "$DIR/install.log" 2>&1
#cd ..
#echo " done!"

#libzip
if [ "$DO_STATIC" == "yes" ]; then
	CMAKE_LIBZIP_EXTRA_FLAGS="-DBUILD_SHARED_LIBS=OFF"
fi
echo -n "[libzip] downloading $LIBZIP_VERSION..."
download_file "https://libzip.org/download/libzip-$LIBZIP_VERSION.tar.gz" | tar -zx >> "$DIR/install.log" 2>&1
mv libzip-$LIBZIP_VERSION libzip >> "$DIR/install.log" 2>&1
echo -n " checking..."
cd libzip
cmake . -DCMAKE_PREFIX_PATH="$DIR/bin/php7" -DCMAKE_INSTALL_PREFIX="$DIR/bin/php7" -DCMAKE_INSTALL_LIBDIR=lib $CMAKE_LIBZIP_EXTRA_FLAGS -DBUILD_TOOLS=OFF -DBUILD_REGRESS=OFF -DBUILD_EXAMPLES=OFF -DBUILD_DOC=OFF >> "$DIR/install.log" 2>&1
echo -n " compiling..."
make -j $THREADS >> "$DIR/install.log" 2>&1
echo -n " installing..."
make install >> "$DIR/install.log" 2>&1
cd ..
echo " done!"



# PECL libraries

# 1: extension name
# 2: extension version
# 3: URL to get .tar.gz from
# 4: Name of extracted directory to move
function get_extension_tar_gz {
	echo -n "  $1: downloading $2..."
	download_file "$3" | tar -zx >> "$DIR/install.log" 2>&1
	mv "$4" "$DIR/install_data/php/ext/$1"
	echo " done!"
}

# 1: extension name
# 2: extension version
# 3: github user name
# 4: github repo name
# 5: version prefix (optional)
function get_github_extension {
	get_extension_tar_gz "$1" "$2" "https://github.com/$3/$4/archive/$5$2.tar.gz" "$4-$2"
}

# 1: extension name
# 2: extension version
function get_pecl_extension {
	get_extension_tar_gz "$1" "$2" "http://pecl.php.net/get/$1-$2.tgz" "$1-$2"
}

echo "[PHP] Downloading additional extensions..."

if [[ "$DO_STATIC" != "yes" ]] && [[ "$COMPILE_DEBUG" == "yes" ]]; then
	get_pecl_extension "xdebug" "$EXT_XDEBUG_VERSION"
fi

#TODO Uncomment this when it's ready for PHP7
#if [ "$COMPILE_DEBUG" == "yes" ]; then
#   get_github_extension "profiler" "master" "krakjoe" "profiler"
#	HAS_PROFILER="--enable-profiler --with-profiler-max-frames=1000"
#else
#	HAS_PROFILER=""
#fi

#get_pecl_extension "ncurses" "$EXT_NCURSES_VERSION"

get_github_extension "pthreads" "$EXT_PTHREADS_VERSION" "pmmp" "pthreads" #"v" needed for release tags because github removes the "v"
#get_pecl_extension "pthreads" "$EXT_PTHREADS_VERSION"

get_github_extension "yaml" "$EXT_YAML_VERSION" "php" "pecl-file_formats-yaml"
#get_pecl_extension "yaml" "$EXT_YAML_VERSION"

get_github_extension "igbinary" "$EXT_IGBINARY_VERSION" "igbinary" "igbinary"

get_github_extension "ds" "$EXT_DS_VERSION" "php-ds" "ext-ds"

get_github_extension "recursionguard" "$EXT_RECURSIONGUARD_VERSION" "pmmp" "ext-recursionguard"

echo -n "  crypto: downloading $EXT_CRYPTO_VERSION..."
git clone https://github.com/bukka/php-crypto.git "$DIR/install_data/php/ext/crypto" >> "$DIR/install.log" 2>&1
cd "$DIR/install_data/php/ext/crypto"
git checkout "$EXT_CRYPTO_VERSION" >> "$DIR/install.log" 2>&1
git submodule update --init --recursive >> "$DIR/install.log" 2>&1
cd "$DIR/install_data"
echo " done!"

if [ "$COMPILE_LEVELDB" == "yes" ]; then
	#PHP LevelDB
	get_github_extension "leveldb" "$EXT_LEVELDB_VERSION" "reeze" "php-leveldb"
	HAS_LEVELDB=--with-leveldb="$DIR/bin/php7"
else
	HAS_LEVELDB=""
fi

if [ "$COMPILE_POCKETMINE_CHUNKUTILS" == "yes" ]; then
	get_github_extension "pocketmine-chunkutils" "$EXT_POCKETMINE_CHUNKUTILS_VERSION" "dktapps" "PocketMine-C-ChunkUtils"
	HAS_POCKETMINE_CHUNKUTILS=--enable-pocketmine-chunkutils
else
	HAS_POCKETMINE_CHUNKUTILS=""
fi


echo -n "[PHP]"

if [ "$DO_OPTIMIZE" != "no" ]; then
	echo -n " enabling optimizations..."
	PHP_OPTIMIZATION="--enable-inline-optimization "
else
	PHP_OPTIMIZATION="--disable-inline-optimization "
fi
echo -n " checking..."
cd php
rm -f ./aclocal.m4 >> "$DIR/install.log" 2>&1
rm -rf ./autom4te.cache/ >> "$DIR/install.log" 2>&1
rm -f ./configure >> "$DIR/install.log" 2>&1

./buildconf --force >> "$DIR/install.log" 2>&1

#hack for curl with pkg-config (ext/curl doesn't give --static to pkg-config on static builds)
if [ "$DO_STATIC" == "yes" ]; then
	if [ -z "$PKG_CONFIG" ]; then
		PKG_CONFIG="$(which pkg-config)" || true
	fi
	if [ ! -z "$PKG_CONFIG" ]; then
		#only export this if pkg-config exists, otherwise leave it (it'll fall back to curl-config)

		echo '#!/bin/sh' > "$DIR/install_data/pkg-config-wrapper"
		echo 'exec '$PKG_CONFIG' "$@" --static' >> "$DIR/install_data/pkg-config-wrapper"
		chmod +x "$DIR/install_data/pkg-config-wrapper"
		export PKG_CONFIG="$DIR/install_data/pkg-config-wrapper"
	fi
fi

if [ "$IS_CROSSCOMPILE" == "yes" ]; then
	sed -i=".backup" 's/pthreads_working=no/pthreads_working=yes/' ./configure
	if [ "$IS_WINDOWS" != "yes" ]; then
		if [ "$COMPILE_FOR_ANDROID" == "no" ]; then
			export LIBS="$LIBS -lpthread -ldl -lresolv"
		else
			export LIBS="$LIBS -lpthread -lresolv"
		fi
	else
		export LIBS="$LIBS -lpthread"
	fi

	mv ext/mysqlnd/config9.m4 ext/mysqlnd/config.m4
	sed  -i=".backup" "s{ext/mysqlnd/php_mysqlnd_config.h{config.h{" ext/mysqlnd/mysqlnd_portability.h
	CONFIGURE_FLAGS="$CONFIGURE_FLAGS --enable-opcache=no"
elif [ "$DO_STATIC" == "yes" ]; then
	export LIBS="$LIBS -ldl"
fi

if [ "$IS_WINDOWS" != "yes" ]; then
	HAVE_PCNTL="--enable-pcntl"
else
	HAVE_PCNTL="--disable-pcntl"
	cp -f ./win32/build/config.* ./main >> "$DIR/install.log" 2>&1
	sed 's:@PREFIX@:$DIR/bin/php7:' ./main/config.w32.h.in > ./wmain/config.w32.h 2>> "$DIR/install.log"
fi

if [[ "$(uname -s)" == "Darwin" ]] && [[ "$IS_CROSSCOMPILE" != "yes" ]]; then
	sed -i=".backup" 's/flock_type=unknown/flock_type=bsd/' ./configure
	export EXTRA_CFLAGS=-lresolv
fi

if [[ "$COMPILE_DEBUG" == "yes" ]]; then
	HAS_DEBUG="--enable-debug"
else
	HAS_DEBUG="--disable-debug"
fi

RANLIB=$RANLIB CFLAGS="$CFLAGS $FLAGS_LTO" CXXFLAGS="$CXXFLAGS $FLAGS_LTO" LDFLAGS="$LDFLAGS $FLAGS_LTO" ./configure $PHP_OPTIMIZATION --prefix="$DIR/bin/php7" \
--exec-prefix="$DIR/bin/php7" \
--with-curl="$DIR/bin/php7" \
--with-zlib="$DIR/bin/php7" \
--with-zlib-dir="$DIR/bin/php7" \
--with-gmp="$DIR/bin/php7" \
--with-yaml="$DIR/bin/php7" \
--with-openssl="$DIR/bin/php7" \
--with-libzip="$DIR/bin/php7" \
$HAS_LIBPNG \
$HAS_LIBJPEG \
$HAS_GD \
$HAVE_NCURSES \
$HAVE_READLINE \
$HAS_LEVELDB \
$HAS_PROFILER \
$HAS_DEBUG \
$HAS_POCKETMINE_CHUNKUTILS \
--enable-mbstring \
--enable-calendar \
--enable-pthreads \
--disable-fileinfo \
--disable-libxml \
--disable-xml \
--disable-dom \
--disable-simplexml \
--disable-xmlreader \
--disable-xmlwriter \
--disable-cgi \
--disable-phpdbg \
--disable-session \
--disable-pdo \
--without-pear \
--without-iconv \
--without-pdo-sqlite \
--with-pic \
--enable-phar \
--enable-ctype \
--enable-sockets \
--enable-shared=no \
--enable-static=yes \
--enable-shmop \
--enable-maintainer-zts \
--disable-short-tags \
$HAVE_PCNTL \
$HAVE_MYSQLI \
--enable-bcmath \
--enable-cli \
--enable-zip \
--enable-ftp \
--enable-opcache=no \
--enable-igbinary \
--enable-ds \
--with-crypto \
--enable-recursionguard \
$HAVE_VALGRIND \
$CONFIGURE_FLAGS >> "$DIR/install.log" 2>&1
echo -n " compiling..."
if [ "$COMPILE_FOR_ANDROID" == "yes" ]; then
	sed -i=".backup" 's/-export-dynamic/-all-static/g' Makefile
fi
sed -i=".backup" 's/PHP_BINARIES. pharcmd$/PHP_BINARIES)/g' Makefile
sed -i=".backup" 's/install-programs install-pharcmd$/install-programs/g' Makefile

if [[ "$COMPILE_LEVELDB" == "yes" ]] && [[ "$DO_STATIC" == "yes" ]]; then
	sed -i=".backup" 's/--mode=link $(CC)/--mode=link $(CXX)/g' Makefile
fi

make -j $THREADS >> "$DIR/install.log" 2>&1
echo -n " installing..."
make install >> "$DIR/install.log" 2>&1

if [[ "$(uname -s)" == "Darwin" ]] && [[ "$IS_CROSSCOMPILE" != "yes" ]]; then
	set +e
	install_name_tool -delete_rpath "$DIR/bin/php7/lib" "$DIR/bin/php7/bin/php" >> "$DIR/install.log" 2>&1

	IFS=$'\n' OTOOL_OUTPUT=($(otool -L "$DIR/bin/php7/bin/php"))

	for (( i=0; i<${#OTOOL_OUTPUT[@]}; i++ ))
		do
		CURRENT_DYLIB_NAME=$(echo ${OTOOL_OUTPUT[$i]} | sed 's# (compatibility version .*##' | xargs)
		if [[ $CURRENT_DYLIB_NAME == "$DIR/bin/php7/lib/"*".dylib"* ]]; then
			NEW_DYLIB_NAME=$(echo "$CURRENT_DYLIB_NAME" | sed "s{$DIR/bin/php7/lib{@loader_path/../lib{" | xargs)
			install_name_tool -change "$CURRENT_DYLIB_NAME" "$NEW_DYLIB_NAME" "$DIR/bin/php7/bin/php" >> "$DIR/install.log" 2>&1
		fi
	done

	install_name_tool -change "$DIR/bin/php7/lib/libssl.1.0.0.dylib" "@loader_path/../lib/libssl.1.0.0.dylib" "$DIR/bin/php7/lib/libcurl.4.dylib" >> "$DIR/install.log" 2>&1
	install_name_tool -change "$DIR/bin/php7/lib/libcrypto.1.0.0.dylib" "@loader_path/../lib/libcrypto.1.0.0.dylib" "$DIR/bin/php7/lib/libcurl.4.dylib" >> "$DIR/install.log" 2>&1
	chmod 0777 "$DIR/bin/php7/lib/libssl.1.0.0.dylib" >> "$DIR/install.log" 2>&1
	install_name_tool -change "$DIR/bin/php7/lib/libcrypto.1.0.0.dylib" "@loader_path/libcrypto.1.0.0.dylib" "$DIR/bin/php7/lib/libssl.1.0.0.dylib" >> "$DIR/install.log" 2>&1
	chmod 0755 "$DIR/bin/php7/lib/libssl.1.0.0.dylib" >> "$DIR/install.log" 2>&1
	set -e
fi

echo -n " generating php.ini..."
trap - DEBUG
TIMEZONE=$(date +%Z)
echo "memory_limit=1024M" >> "$DIR/bin/php7/bin/php.ini"
echo "date.timezone=$TIMEZONE" >> "$DIR/bin/php7/bin/php.ini"
echo "short_open_tag=0" >> "$DIR/bin/php7/bin/php.ini"
echo "asp_tags=0" >> "$DIR/bin/php7/bin/php.ini"
echo "phar.readonly=0" >> "$DIR/bin/php7/bin/php.ini"
echo "phar.require_hash=1" >> "$DIR/bin/php7/bin/php.ini"
echo "igbinary.compact_strings=0" >> "$DIR/bin/php7/bin/php.ini"
if [[ "$COMPILE_DEBUG" == "yes" ]]; then
	echo "zend.assertions=1" >> "$DIR/bin/php7/bin/php.ini"
else
	echo "zend.assertions=-1" >> "$DIR/bin/php7/bin/php.ini"
fi
echo "error_reporting=-1" >> "$DIR/bin/php7/bin/php.ini"
echo "display_errors=1" >> "$DIR/bin/php7/bin/php.ini"
echo "display_startup_errors=1" >> "$DIR/bin/php7/bin/php.ini"
echo "recursionguard.enabled=0 ;disabled due to minor performance impact, only enable this if you need it for debugging" >> "$DIR/bin/php7/bin/php.ini"

if [ "$IS_CROSSCOMPILE" != "yes" ] && [ "$DO_STATIC" == "no" ]; then
	echo ";zend_extension=opcache.so" >> "$DIR/bin/php7/bin/php.ini"
	echo "opcache.enable=1" >> "$DIR/bin/php7/bin/php.ini"
	echo "opcache.enable_cli=1" >> "$DIR/bin/php7/bin/php.ini"
	echo "opcache.save_comments=1" >> "$DIR/bin/php7/bin/php.ini"
	echo "opcache.fast_shutdown=0" >> "$DIR/bin/php7/bin/php.ini"
	echo "opcache.max_accelerated_files=4096" >> "$DIR/bin/php7/bin/php.ini"
	echo "opcache.interned_strings_buffer=8" >> "$DIR/bin/php7/bin/php.ini"
	echo "opcache.memory_consumption=128" >> "$DIR/bin/php7/bin/php.ini"
	echo "opcache.optimization_level=0xffffffff" >> "$DIR/bin/php7/bin/php.ini"
fi

echo " done!"

if [[ "$DO_STATIC" != "yes" ]] && [[ "$COMPILE_DEBUG" == "yes" ]]; then
	echo -n "[xdebug] checking..."
	cd "$DIR/install_data/php/ext/xdebug"
	$DIR/bin/php7/bin/phpize >> "$DIR/install.log" 2>&1
	./configure --with-php-config="$DIR/bin/php7/bin/php-config" >> "$DIR/install.log" 2>&1
	echo -n " compiling..."
	make -j4 >> "$DIR/install.log" 2>&1
	echo -n " installing..."
	make install >> "$DIR/install.log" 2>&1
	echo "zend_extension=xdebug.so" >> "$DIR/bin/php7/bin/php.ini"
	echo " done!"
fi

cd "$DIR"
if [ "$DO_CLEANUP" == "yes" ]; then
	echo -n "[INFO] Cleaning up..."
	rm -r -f install_data/ >> "$DIR/install.log" 2>&1
	rm -f bin/php7/bin/curl* >> "$DIR/install.log" 2>&1
	rm -f bin/php7/bin/curl-config* >> "$DIR/install.log" 2>&1
	rm -f bin/php7/bin/c_rehash* >> "$DIR/install.log" 2>&1
	rm -f bin/php7/bin/openssl* >> "$DIR/install.log" 2>&1
	rm -r -f bin/php7/man >> "$DIR/install.log" 2>&1
	rm -r -f bin/php7/share/man >> "$DIR/install.log" 2>&1
	rm -r -f bin/php7/php >> "$DIR/install.log" 2>&1
	rm -r -f bin/php7/misc >> "$DIR/install.log" 2>&1
	rm -r -f bin/php7/lib/*.a >> "$DIR/install.log" 2>&1
	rm -r -f bin/php7/lib/*.la >> "$DIR/install.log" 2>&1
	rm -r -f bin/php7/include >> "$DIR/install.log" 2>&1
	echo " done!"
fi

date >> "$DIR/install.log" 2>&1
echo "[PocketMine] You should start the server now using \"./start.sh\"."
echo "[PocketMine] If it doesn't work, please send the \"install.log\" file to the Bug Tracker."
