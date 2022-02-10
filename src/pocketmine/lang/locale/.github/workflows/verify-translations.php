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

namespace pocketmine\build\generate_known_translation_apis;

use function count;
use function fwrite;
use function in_array;
use function parse_ini_file;
use function preg_match;
use function preg_match_all;
use const INI_SCANNER_RAW;
use const PHP_EOL;
use const STDERR;

/**
 * @param string[]                      $baseLanguageDef
 * @param string[]                      $altLanguageDef
 *
 * @phpstan-param array<string, string> $baseLanguageDef
 * @phpstan-param array<string, string> $altLanguageDef
 *
 * @return bool true if everything is OK, false otherwise
 */
function verify_translations(array $baseLanguageDef, string $altLanguageName, array $altLanguageDef) : bool{
	$parameterRegex = '/{%(.+?)}/';

	$ok = true;
	foreach($baseLanguageDef as $key => $baseString){
		if(!isset($altLanguageDef[$key])){
			continue;
		}
		$altString = $altLanguageDef[$key];
		$baseParams = preg_match_all($parameterRegex, $baseString, $baseMatches);
		$altParams = preg_match_all($parameterRegex, $altString, $altMatches);
		if($baseParams === false || $altParams === false){
			throw new \Error("preg_match_all() should not have failed here");
		}
		foreach($baseMatches[1] as $paramName){
			if(!in_array($paramName, $altMatches[1], true)){
				fwrite(STDERR, "$altLanguageName: missing parameter %$paramName in string $key" . PHP_EOL);
				$ok = false;
			}
		}
		foreach($altMatches[1] as $paramName){
			if(!in_array($paramName, $baseMatches[1], true)){
				fwrite(STDERR, "$altLanguageName: unexpected extra parameter %$paramName in string $key" . PHP_EOL);
				$ok = false;
			}
		}
	}
	foreach($altLanguageDef as $key => $altString){
		if(!isset($baseLanguageDef[$key])){
			fwrite(STDERR, "$altLanguageName: unexpected extra string $key with no base in eng.ini" . PHP_EOL);
			$ok = false;
		}
	}
	return $ok;
}

function parse_language_file(string $path, string $code) : ?array{
	$lang = parse_ini_file($path . "/" . "$code.ini", false, INI_SCANNER_RAW);
	if($lang === false){
		return null;
	}
	return $lang;
}

if(count($argv) !== 2){
	fwrite(STDERR, "Required arguments: path\n");
	exit(1);
}
$eng = parse_language_file($argv[1], "eng");
if($eng === null){
	fwrite(STDERR, "Failed to parse eng.ini\n");
	exit(1);
}
$exit = 0;
foreach(new \RegexIterator(new \FilesystemIterator($argv[1], \FilesystemIterator::CURRENT_AS_PATHNAME), "/([a-z]+)\.ini$/", \RegexIterator::GET_MATCH) as $match){
	$code = $match[1];
	if($code === "eng"){
		continue;
	}
	$otherLang = parse_language_file($argv[1], $code);
	if($otherLang === null){
		fwrite(STDERR, "Error parsing $code.ini\n");
		$exit = 1;
		continue;
	}
	if(!verify_translations($eng, $code, $otherLang)){
		fwrite(STDERR, "Errors found in $code.ini\n");
		$exit = 1;
		continue;
	}

	echo "Everything OK in $code.ini\n";
}
exit($exit);
