# Custom PHP build scripts for PocketMine-MP
[![Build Status](https://dev.azure.com/pocketmine/PHP-Builds/_apis/build/status/pmmp.php-build-scripts)](https://dev.azure.com/pocketmine/PHP-Builds/_build?definitionId=3&_a=summary&view=branches)
## Looking for prebuilt binaries? Head over to our [Jenkins build server](https://jenkins.pmmp.io/job/PHP-7.2-Aggregate)

## compile.sh

Bash script used to compile PHP on MacOS and Linux platforms. Make sure you have ``make autoconf automake libtool m4 wget getconf gzip bzip2 bison g++ git cmake``.

### Additional notes
#### Mac OSX (native compile)
- Most dependencies can be installed using Homebrew
- You will additionally need `glibtool` (GNU libtool, xcode libtool won't work)
- You also MUST specify target as `mac` or `mac64` if building for Mac, on Mac.

#### Android 64-bit (cross-compile)
- Only aarch64 targets are supported for Android cross-compile.
- The `aarch64-linux-musl` toolchain is required. You can compile and install it using https://github.com/pmmp/musl-cross-make (PMMP fork includes musl-libc patches for DNS resolver config path and increasing stack size limit for LevelDB)
- Android cross-compile binaries MUST be compiled statically (using `-s`) or the binary will not work correctly.
- It is strongly recommended that you enable abusive optimizations for Android targets (`-f` flag) for best performance.

| Script flags | Description                                                                           |
| ------------ | ------------------------------------------------------------------------------------- |
| -d           | Will compile with debug and the xdebug PHP extension                                  |
| -f           | Enabling abusive optimizations...                                                     |
| -g           | Will compile GD2                                                                      |
| -j           | Set make threads to #                                                                 |
| -l           | Will compile with LevelDB support (experimental with PHP7)                            |
| -s           | Will compile everything statically                                                    |
| -t           | Set target                                                                            |
| -u           | Will compile PocketMine-ChunkUtils C extension (recommended if using PC Anvil worlds) |
| -x           | Specifies we are doing cross-compile                                                  |

### Example:

| Target          | Arguments                           |
| --------------- | ----------------------------------- |
| linux64         | ``-t linux64 -l -j4 -f x86_64``     |
| mac64           | ``-t mac64 -l -j4 -f``              |
| android-aarch64 | ``-t android-aarch64 -x -s -j4 -f`` |

### Common pitfalls
- If used, the `-t` option (target) MUST be specified BEFORE the `-f` option (optimizations)
- Avoid using the script in directory trees containing spaces. Some libraries don't like trying to be built in directory trees containing spaces, e.g. `/home/user/my folder/pocketmine-mp/` might experience problems.

## installer.sh

Script to install PocketMine-MP and PHP binaries on Unix platforms.

| Flag   | Description                         |
| ------ | ----------------------------------- |
| -u     | Update PocketMine-MP                |
| -d     | Install directory                   |
| -v     | Channel (stable or development)     |


## windows-compile-vs.bat

Batch script utilizing Visual Studio on Windows to compile PHP binaries from sources.
Ensure you have Visual Studio 2017, `git`, `7z` and `wget` installed in your PATH.

Prebuilt binaries can be downloaded from our [AppVeyor build job](https://ci.appveyor.com/project/pmmp/php-build-scripts/build/artifacts).
