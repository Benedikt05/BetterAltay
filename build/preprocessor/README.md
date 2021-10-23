# PreProcessor
Scripts used to optimise PocketMine-MP before building phars.

These scripts are used by Jenkins to optimize PocketMine-MP source code in production phars.

### PreProcessor.php
This script uses the C preprocessor to pre-process PocketMine-MP source code before it is packaged into a phar. The headers in the `rules/` directory define C macros which are used to preprocess the code and optimize it for use in production.

#### Arguments
- `path`: Path to the PocketMine-MP source code to optimize.
- `multisize`: Whether to produce multiple optimized code versions with optimizations specific to 64-bit or 32-bit platforms. Where applicable, multiple versions of target source files will be produced with different optimizations (for example, `Player__64bit.php`, `Player__32bit.php`). The autoloader in PocketMine-MP will then decide which version of the source file to load at runtime based on the platform.
