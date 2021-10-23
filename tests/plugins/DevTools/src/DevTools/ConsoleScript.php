<?php

declare(strict_types=1);

/*
 * DevTools plugin for PocketMine-MP
 * Copyright (C) 2014 PocketMine Team <https://github.com/PocketMine/DevTools>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

const DEVTOOLS_VERSION = "1.14.2";

const DEVTOOLS_REQUIRE_FILE_STUB = '<?php require("phar://" . __FILE__ . "/%s"); __HALT_COMPILER();';
const DEVTOOLS_PLUGIN_STUB = '
<?php
echo "PocketMine-MP plugin %s v%s
This file has been generated using DevTools v%s at %s
----------------
%s
";
__HALT_COMPILER();
';

/**
 * @param string[]    $strings
 * @param string|null $delim
 *
 * @return string[]
 */
function preg_quote_array(array $strings, string $delim = null) : array{
	return array_map(function(string $str) use ($delim) : string{ return preg_quote($str, $delim); }, $strings);
}

/**
 * @param string   $pharPath
 * @param string   $basePath
 * @param string[] $includedPaths
 * @param mixed[]  $metadata
 * @param string   $stub
 * @param int      $signatureAlgo
 * @param int|null $compression
 * @phpstan-param array<string, mixed> $metadata
 *
 * @return Generator|string[]
 */
function buildPhar(string $pharPath, string $basePath, array $includedPaths, array $metadata, string $stub, int $signatureAlgo = \Phar::SHA1, ?int $compression = null){
	$basePath = rtrim(str_replace("/", DIRECTORY_SEPARATOR, $basePath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	$includedPaths = array_map(function($path) : string{
		$path = rtrim(str_replace("/", DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
		return is_dir($path) ? $path . DIRECTORY_SEPARATOR : $path;
	}, $includedPaths);
	if(file_exists($pharPath)){
		yield "Phar file already exists, overwriting...";
		try{
			\Phar::unlinkArchive($pharPath);
		}catch(\PharException $e){
			//unlinkArchive() doesn't like dodgy phars
			unlink($pharPath);
		}
	}

	yield "Adding files...";

	$start = microtime(true);
	$phar = new \Phar($pharPath);
	$phar->setMetadata($metadata);
	$phar->setStub($stub);
	$phar->setSignatureAlgorithm($signatureAlgo);
	$phar->startBuffering();

	//If paths contain any of these, they will be excluded
	$excludedSubstrings = preg_quote_array([
		realpath($pharPath), //don't add the phar to itself
	], '/');

	$folderPatterns = preg_quote_array([
		DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR,
		DIRECTORY_SEPARATOR . '.' //"Hidden" files, git dirs etc
	], '/');

	//Only exclude these within the basedir, otherwise the project won't get built if it itself is in a directory that matches these patterns
	$basePattern = preg_quote(rtrim($basePath, DIRECTORY_SEPARATOR), '/');
	foreach($folderPatterns as $p){
		$excludedSubstrings[] = $basePattern . '.*' . $p;
	}

	$regex = sprintf('/^(?!.*(%s))^%s(%s).*/i',
		 implode('|', $excludedSubstrings), //String may not contain any of these substrings
		 preg_quote($basePath, '/'), //String must start with this path...
		 implode('|', preg_quote_array($includedPaths, '/')) //... and must be followed by one of these relative paths, if any were specified. If none, this will produce a null capturing group which will allow anything.
	);

	$directory = new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::CURRENT_AS_PATHNAME); //can't use fileinfo because of symlinks
	$iterator = new \RecursiveIteratorIterator($directory);
	$regexIterator = new \RegexIterator($iterator, $regex);

	$count = count($phar->buildFromIterator($regexIterator, $basePath));
	yield "Added $count files";

	if($compression !== null){
		yield "Checking for compressible files...";
		foreach($phar as $file => $finfo){
			/** @var \PharFileInfo $finfo */
			if($finfo->getSize() > (1024 * 512)){
				yield "Compressing " . $finfo->getFilename();
				$finfo->compress($compression);
			}
		}
	}
	$phar->stopBuffering();

	yield "Done in " . round(microtime(true) - $start, 3) . "s";
}

/**
 * @return mixed[]|null
 * @phpstan-return array<string, mixed>|null
 */
function generatePluginMetadataFromYml(string $pluginYmlPath) : ?array{
	if(!file_exists($pluginYmlPath)){
		return null;
	}

	$pluginYml = yaml_parse_file($pluginYmlPath);
	return [
		"name" => $pluginYml["name"],
		"version" => $pluginYml["version"],
		"main" => $pluginYml["main"],
		"api" => $pluginYml["api"],
		"depend" => $pluginYml["depend"] ?? "",
		"description" => $pluginYml["description"] ?? "",
		"authors" => $pluginYml["authors"] ?? "",
		"website" => $pluginYml["website"] ?? "",
		"creationDate" => time()
	];
}

function main() : void{
	$opts = getopt("", ["make:", "relative:", "out:", "entry:", "compress", "stub:"]);
	global $argv;

	if(!isset($opts["make"])){
		echo "== PocketMine-MP DevTools CLI interface ==" . PHP_EOL . PHP_EOL;
		echo "Usage: " . PHP_BINARY . " -dphar.readonly=0 " . $argv[0] . " --make <sourceFolder1[,sourceFolder2[,sourceFolder3...]]> --relative <relativePath> --entry \"relativeSourcePath.php\" --out <pharName.phar>" . PHP_EOL;
		exit(0);
	}

	if(ini_get("phar.readonly") == 1){
		echo "Set phar.readonly to 0 with -dphar.readonly=0" . PHP_EOL;
		exit(1);
	}

	$includedPaths = explode(",", $opts["make"]);
	array_walk($includedPaths, function(&$path, $key) : void{
		$realPath = realpath($path);
		if($realPath === false){
			echo "[ERROR] make directory `$path` does not exist or permission denied" . PHP_EOL;
			exit(1);
		}

		//Convert to absolute path for base path detection
		if(is_dir($realPath)){
			$path = rtrim($realPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		}
	});

	if(!isset($opts["relative"])){
		if(count($includedPaths) > 1){
			echo "You must specify a relative path with --relative <path> to be able to include multiple directories" . PHP_EOL;
			exit(1);
		}

		$basePath = rtrim(realpath(array_shift($includedPaths)), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}else{
		$basePath = rtrim(realpath($opts["relative"]), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}

	//Convert included paths back to relative after we decide what the base path is
	$includedPaths = array_filter(array_map(function(string $path) use ($basePath) : string{
		return str_replace($basePath, '', $path);
	}, $includedPaths), function(string $v) : bool{
		return $v !== '';
	});

	$pharName = $opts["out"] ?? "output.phar";
	$stubPath = $opts["stub"] ?? "stub.php";

	if(!is_dir($basePath)){
		echo $basePath . " is not a folder" . PHP_EOL;
		return;
	}

	echo PHP_EOL;

	$metadata = [];

	if(file_exists($basePath . $stubPath)){
		echo "Using stub " . $basePath . $stubPath . PHP_EOL;
		$stub = sprintf(DEVTOOLS_REQUIRE_FILE_STUB, $stubPath);
	}elseif(isset($opts["entry"])){
		$realEntry = realpath($opts["entry"]);
		if($realEntry === false){
			die("Entry point not found");
		}

		$realEntry = addslashes(str_replace([$basePath, "\\"], ["", "/"], $realEntry));
		echo "Setting entry point to " . $realEntry . PHP_EOL;

		$stub = sprintf(DEVTOOLS_REQUIRE_FILE_STUB, $realEntry);
	}else{
		$metadata = generatePluginMetadataFromYml($basePath . "plugin.yml");
		if($metadata === null){
			echo "Missing entry point or plugin.yml" . PHP_EOL;
			exit(1);
		}

		$stubMetadata = [];
		foreach($metadata as $key => $value){
			$stubMetadata[] = addslashes(ucfirst($key) . ": " . (is_array($value) ? implode(", ", $value) : $value));
		}
		$stub = sprintf(DEVTOOLS_PLUGIN_STUB, $metadata["name"], $metadata["version"], DEVTOOLS_VERSION, date("r"), implode("\n", $stubMetadata));
	}

	echo PHP_EOL;

	foreach(buildPhar($pharName, $basePath, $includedPaths, $metadata, $stub) as $line){
		echo $line . PHP_EOL;
	}
}

if(!class_exists(\DevTools\DevTools::class)){
	main();
}
