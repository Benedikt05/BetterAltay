<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine {

	use Composer\InstalledVersions;
	use pocketmine\utils\Git;
	use pocketmine\utils\MainLogger;
	use pocketmine\utils\Process;
	use pocketmine\utils\ServerKiller;
	use pocketmine\utils\Terminal;
	use pocketmine\utils\Timezone;
	use pocketmine\utils\Utils;
	use pocketmine\utils\VersionString;
	use pocketmine\wizard\SetupWizard;

	require_once __DIR__ . '/VersionInfo.php';

	const MIN_PHP_VERSION = "8.0.0";

	/**
	 * @param string $message
	 * @return void
	 */
	function critical_error($message){
		echo "[ERROR] $message" . PHP_EOL;
	}

	/*
	 * Startup code. Do not look at it, it may harm you.
	 * This is the only non-class based file on this project.
	 * Enjoy it as much as I did writing it. I don't want to do it again.
	 */

	/**
	 * @return string[]
	 */
	function check_platform_dependencies(){
		if(version_compare(MIN_PHP_VERSION, PHP_VERSION) > 0){
			//If PHP version isn't high enough, anything below might break, so don't bother checking it.
			return [
				"PHP >= " . MIN_PHP_VERSION . " is required, but you have PHP " . PHP_VERSION . "."
			];
		}

		$messages = [];

		if(PHP_INT_SIZE < 8){
			$messages[] = "32-bit systems/PHP are no longer supported. Please upgrade to a 64-bit system, or use a 64-bit PHP binary if this is a 64-bit system.";
		}

		if(php_sapi_name() !== "cli"){
			$messages[] = "Only PHP CLI is supported.";
		}

		$extensions = [
			"chunkutils2" => "PocketMine ChunkUtils v2",
			"curl" => "cURL",
            "crypto" => "php-crypto",
			"ctype" => "ctype",
			"date" => "Date",
			"hash" => "Hash",
			"json" => "JSON",
			"mbstring" => "Multibyte String",
			"openssl" => "OpenSSL",
			"pcre" => "PCRE",
			"phar" => "Phar",
			"pthreads" => "pthreads",
			"reflection" => "Reflection",
			"sockets" => "Sockets",
			"spl" => "SPL",
			"yaml" => "YAML",
			"zip" => "Zip",
			"zlib" => "Zlib"
		];

		foreach($extensions as $ext => $name){
			if(!extension_loaded($ext)){
				$messages[] = "Unable to find the $name ($ext) extension.";
			}
		}

		if(extension_loaded("pthreads")){
			$pthreads_version = phpversion("pthreads");
			if(substr_count($pthreads_version, ".") < 2){
				$pthreads_version = "0.$pthreads_version";
			}
			if(version_compare($pthreads_version, "4.0.0") < 0 || version_compare($pthreads_version, "5.0.0") > 0){
				$messages[] = "pthreads ^4.0.0 is required, while you have $pthreads_version.";
			}
		}

		if(extension_loaded("leveldb")){
			$leveldb_version = phpversion("leveldb");
			if(version_compare($leveldb_version, "0.2.1") < 0){
				$messages[] = "php-leveldb >= 0.2.1 is required, while you have $leveldb_version.";
			}
		}

		$chunkutils2_version = phpversion("chunkutils2");
		$wantedVersionLock = "0.3";
		$wantedVersionMin = "$wantedVersionLock.0";
		if($chunkutils2_version !== false && (
				version_compare($chunkutils2_version, $wantedVersionMin) < 0 ||
				preg_match("/^" . preg_quote($wantedVersionLock, "/") . "\.\d+(?:-dev)?$/", $chunkutils2_version) === 0 //lock in at ^0.2, optionally at a patch release
			)){
			$messages[] = "chunkutils2 ^$wantedVersionMin is required, while you have $chunkutils2_version.";
		}

		if(extension_loaded("pocketmine")){
			$messages[] = "The native PocketMine extension is no longer supported.";
		}

		return $messages;
	}

	/**
	 * @param \Logger $logger
	 * @return void
	 */
	function emit_performance_warnings(\Logger $logger){
		if(PHP_DEBUG !== 0){
			$logger->warning("This PHP binary was compiled in debug mode. This has a major impact on performance.");
		}
		if(extension_loaded("xdebug")){
			$logger->warning("Xdebug extension is enabled. This has a major impact on performance.");
		}
		if(!extension_loaded("pocketmine_chunkutils")){
			$logger->warning("ChunkUtils extension is missing. Anvil-format worlds will experience degraded performance.");
		}
		if(((int) ini_get('zend.assertions')) !== -1){
			$logger->warning("Debugging assertions are enabled. This may degrade performance. To disable them, set `zend.assertions = -1` in php.ini.");
		}
		if(\Phar::running(true) === ""){
			$logger->warning("Non-packaged installation detected. This will degrade autoloading speed and make startup times longer.");
		}
		if(function_exists('opcache_get_status') && ($opcacheStatus = opcache_get_status(false)) !== false){
			$jitEnabled = $opcacheStatus["jit"]["on"] ?? false;
			if($jitEnabled !== false){
				$logger->warning(<<<'JIT_WARNING'


	--------------------------------------- ! WARNING ! ---------------------------------------
	You're using PHP 8.0 with JIT enabled. This provides significant performance improvements.
	HOWEVER, it is EXPERIMENTAL, and has already been seen to cause weird and unexpected bugs.
	Proceed with caution.
	If you want to report any bugs, make sure to mention that you are using PHP 8.0 with JIT.
	To turn off JIT, change `opcache.jit` to `0` in your php.ini file.
	-------------------------------------------------------------------------------------------

JIT_WARNING
);
			}
		}
	}

	/**
	 * @return void
	 */
	function set_ini_entries(){
		ini_set("allow_url_fopen", '1');
		ini_set("display_errors", '1');
		ini_set("display_startup_errors", '1');
		ini_set("default_charset", "utf-8");
		ini_set('assert.exception', '1');
	}

	/**
	 * @return void
	 */
	function server(){
		if(count($messages = check_platform_dependencies()) > 0){
			echo PHP_EOL;
			$binary = version_compare(PHP_VERSION, "5.4") >= 0 ? PHP_BINARY : "unknown";
			critical_error("Selected PHP binary does not satisfy some requirements.");
			foreach($messages as $m){
				echo " - $m" . PHP_EOL;
			}
			critical_error("PHP binary used: " . $binary);
			critical_error("Loaded php.ini: " . (($file = php_ini_loaded_file()) !== false ? $file : "none"));
			$phprc = getenv("PHPRC");
			critical_error("Value of PHPRC environment variable: " . ($phprc === false ? "" : $phprc));
			critical_error("Please recompile PHP with the needed configuration, or refer to the installation instructions at http://pmmp.rtfd.io/en/rtfd/installation.html.");
			echo PHP_EOL;
			exit(1);
		}
		unset($messages);

		error_reporting(-1);
		set_ini_entries();

		$opts = getopt("", ["bootstrap:"]);
		if(isset($opts["bootstrap"])){
			$bootstrap = ($real = realpath($opts["bootstrap"])) !== false ? $real : $opts["bootstrap"];
		}else{
			$bootstrap = dirname(__FILE__, 3) . '/vendor/autoload.php';
		}

		if($bootstrap === false or !is_file($bootstrap)){
			critical_error("Composer autoloader not found at " . $bootstrap);
			critical_error("Please install/update Composer dependencies or use provided builds.");
			exit(1);
		}
		define('pocketmine\COMPOSER_AUTOLOADER_PATH', $bootstrap);
		require_once(\pocketmine\COMPOSER_AUTOLOADER_PATH);

		set_error_handler([Utils::class, 'errorExceptionHandler']);

		$gitHash = str_repeat("00", 20);
		$buildNumber = 0;

		if(\Phar::running(true) === ""){
			$gitHash = Git::getRepositoryStatePretty(\pocketmine\PATH);
		}else{
			$phar = new \Phar(\Phar::running(false));
			$meta = $phar->getMetadata();
			if(isset($meta["git"])){
				$gitHash = $meta["git"];
			}
			if(isset($meta["build"]) && is_int($meta["build"])){
				$buildNumber = $meta["build"];
			}
		}

		define('pocketmine\GIT_COMMIT', $gitHash);
		define('pocketmine\BUILD_NUMBER', $buildNumber);

		$version = new VersionString(\pocketmine\BASE_VERSION, \pocketmine\IS_DEVELOPMENT_BUILD, \pocketmine\BUILD_NUMBER);
		define('pocketmine\VERSION', $version->getFullVersion(true));

		$composerGitHash = InstalledVersions::getReference('pocketmine/pocketmine-mp');
		if($composerGitHash !== null){
			$currentGitHash = explode("-", \pocketmine\GIT_COMMIT)[0];
			if($currentGitHash !== $composerGitHash){
				critical_error("Composer dependencies and/or autoloader are out of sync.");
				critical_error("- Current revision is $currentGitHash");
				critical_error("- Composer dependencies were last synchronized for revision $composerGitHash");
				critical_error("Out-of-sync Composer dependencies may result in crashes and classes not being found.");
				critical_error("Please synchronize Composer dependencies before running the server.");
				exit(1);
			}
		}

		$opts = getopt("", ["data:", "plugins:", "no-wizard", "enable-ansi", "disable-ansi"]);

		define('pocketmine\DATA', isset($opts["data"]) ? $opts["data"] . DIRECTORY_SEPARATOR : realpath(getcwd()) . DIRECTORY_SEPARATOR);
		define('pocketmine\PLUGIN_PATH', isset($opts["plugins"]) ? $opts["plugins"] . DIRECTORY_SEPARATOR : realpath(getcwd()) . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR);

		if(!file_exists(\pocketmine\DATA)){
			mkdir(\pocketmine\DATA, 0777, true);
		}

		$lockFile = fopen(\pocketmine\DATA . 'server.lock', "a+b");
		if($lockFile === false){
			critical_error("Unable to open server.lock file. Please check that the current user has read/write permissions to it.");
			exit(1);
		}
		define('pocketmine\LOCK_FILE', $lockFile);
		if(!flock(\pocketmine\LOCK_FILE, LOCK_EX | LOCK_NB)){
			//wait for a shared lock to avoid race conditions if two servers started at the same time - this makes sure the
			//other server wrote its PID and released exclusive lock before we get our lock
			flock(\pocketmine\LOCK_FILE, LOCK_SH);
			$pid = stream_get_contents(\pocketmine\LOCK_FILE);
			critical_error("Another " . \pocketmine\NAME . " instance (PID $pid) is already using this folder (" . realpath(\pocketmine\DATA) . ").");
			critical_error("Please stop the other server first before running a new one.");
			exit(1);
		}
		ftruncate(\pocketmine\LOCK_FILE, 0);
		fwrite(\pocketmine\LOCK_FILE, (string) getmypid());
		fflush(\pocketmine\LOCK_FILE);
		flock(\pocketmine\LOCK_FILE, LOCK_SH); //prevent acquiring an exclusive lock from another process, but allow reading

		//Logger has a dependency on timezone
		$tzError = Timezone::init();

		if(isset($opts["enable-ansi"])){
			Terminal::init(true);
		}elseif(isset($opts["disable-ansi"])){
			Terminal::init(false);
		}else{
			Terminal::init();
		}

		$logger = new MainLogger(\pocketmine\DATA . "server.log");
		$logger->registerStatic();

		foreach($tzError as $e){
			$logger->warning($e);
		}
		unset($tzError);

		emit_performance_warnings($logger);

		$exitCode = 0;
		do{
			if(!file_exists(\pocketmine\DATA . "server.properties") and !isset($opts["no-wizard"])){
				$installer = new SetupWizard();
				if(!$installer->run()){
					$exitCode = -1;
					break;
				}
			}

			//TODO: move this to a Server field
			define('pocketmine\START_TIME', microtime(true));

			/*
			 * We now use the Composer autoloader, but this autoloader is still for loading plugins.
			 */
			$autoloader = new \BaseClassLoader();
			$autoloader->register(false);

			new Server($autoloader, $logger, \pocketmine\DATA, \pocketmine\PLUGIN_PATH);

			$logger->info("Stopping other threads");

			$killer = new ServerKiller(8);
			$killer->start(PTHREADS_INHERIT_NONE);
			usleep(10000); //Fixes ServerKiller not being able to start on single-core machines

			if(ThreadManager::getInstance()->stopAll() > 0){
				$logger->debug("Some threads could not be stopped, performing a force-kill");
				Process::kill(Process::pid());
			}
		}while(false);

		$logger->shutdown();
		$logger->join();

		echo Terminal::$FORMAT_RESET . PHP_EOL;

		if(!flock(\pocketmine\LOCK_FILE, LOCK_UN)){
			critical_error("Failed to release the server.lock file.");
		}

		if(!fclose(\pocketmine\LOCK_FILE)){
			critical_error("Could not close server.lock resource.");
		}

		exit($exitCode);
	}

	\pocketmine\server();
}
