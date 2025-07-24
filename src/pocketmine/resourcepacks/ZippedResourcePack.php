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

namespace pocketmine\resourcepacks;

use Ahc\Json\Comment as CommentedJsonDecoder;
use function assert;
use function count;
use function fclose;
use function feof;
use function file_exists;
use function filesize;
use function fopen;
use function fread;
use function fseek;
use function gettype;
use function hash_file;
use function implode;
use function preg_match;
use function strlen;

class ZippedResourcePack implements ResourcePack{

	/**
	 * Performs basic validation checks on a resource pack's manifest.json.
	 * TODO: add more manifest validation
	 */
	public static function verifyManifest(\stdClass $manifest) : bool{
		if(!isset($manifest->format_version) or !isset($manifest->header) or !isset($manifest->modules)){
			return false;
		}

		//Right now we don't care about anything else, only the stuff we're sending to clients.
		return
			isset($manifest->header->description) and
			isset($manifest->header->name) and
			isset($manifest->header->uuid) and
			isset($manifest->header->version) and
			count($manifest->header->version) === 3;
	}

	/** @var string */
	protected $path;

	/** @var \stdClass */
	protected $manifest;

	/** @var string|null */
	protected $sha256 = null;

	/** @var resource */
	protected $fileResource;

	/** @var string|null  */
	protected ?string $encryptionKey = null;

	/**
	 * @param string $zipPath Path to the resource pack zip
	 * @throws ResourcePackException
	 */
	public function __construct(string $zipPath){
		$this->path = $zipPath;

		if(!file_exists($zipPath)){
			throw new ResourcePackException("File not found");
		}

		$archive = new \ZipArchive();
		if(($openResult = $archive->open($zipPath)) !== true){
			throw new ResourcePackException("Encountered ZipArchive error code $openResult while trying to open $zipPath");
		}

		if(($key = $archive->getFromName("encryption.key")) !== false){
			$this->encryptionKey = $key;
		}

		if(($manifestData = $archive->getFromName("manifest.json")) === false){
			$manifestPath = null;
			$manifestIdx = null;
			for($i = 0; $i < $archive->numFiles; ++$i){
				$name = $archive->getNameIndex($i);
				if(
					($manifestPath === null or strlen($name) < strlen($manifestPath)) and
					preg_match('#.*/manifest.json$#', $name) === 1
				){
					$manifestPath = $name;
					$manifestIdx = $i;
				}
			}
			if($manifestIdx !== null){
				$manifestData = $archive->getFromIndex($manifestIdx);
				assert($manifestData !== false);
			}elseif($archive->locateName("pack_manifest.json") !== false){
				throw new ResourcePackException("Unsupported old pack format");
			}else{
				throw new ResourcePackException("manifest.json not found in the archive root");
			}
		}

		$archive->close();

		//maybe comments in the json, use stripped decoder (thanks mojang)
		try{
			$manifest = (new CommentedJsonDecoder())->decode($manifestData);
		}catch(\RuntimeException $e){
			throw new ResourcePackException("Failed to parse manifest.json: " . $e->getMessage(), $e->getCode(), $e);
		}
		if(!($manifest instanceof \stdClass)){
			throw new ResourcePackException("manifest.json should contain a JSON object, not " . gettype($manifest));
		}
		if(!self::verifyManifest($manifest)){
			throw new ResourcePackException("manifest.json is missing required fields");
		}

		$this->manifest = $manifest;

		$this->fileResource = fopen($zipPath, "rb");
	}

	public function __destruct(){
		fclose($this->fileResource);
	}

	public function getPath() : string{
		return $this->path;
	}

	public function getPackName() : string{
		return $this->manifest->header->name;
	}

	public function getPackVersion() : string{
		return implode(".", $this->manifest->header->version);
	}

	public function getPackId() : string{
		return $this->manifest->header->uuid;
	}

	public function getPackSize() : int{
		return filesize($this->path);
	}

	public function getEncryptionKey() : ?string{
		return $this->encryptionKey;
	}

	public function getSha256(bool $cached = true) : string{
		if($this->sha256 === null or !$cached){
			$this->sha256 = hash_file("sha256", $this->path, true);
		}
		return $this->sha256;
	}

	public function getPackChunk(int $start, int $length) : string{
		fseek($this->fileResource, $start);
		if(feof($this->fileResource)){
			throw new \InvalidArgumentException("Requested a resource pack chunk with invalid start offset");
		}
		return fread($this->fileResource, $length);
	}
}
