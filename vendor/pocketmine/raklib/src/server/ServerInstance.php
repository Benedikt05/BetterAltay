<?php

/*
 * RakLib network library
 *
 *
 * This project is not affiliated with Jenkins Software LLC nor RakNet.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace raklib\server;

use raklib\protocol\EncapsulatedPacket;

interface ServerInstance{

	public function openSession(string $identifier, string $address, int $port, int $clientID) : void;

	public function closeSession(string $identifier, string $reason) : void;

	public function handleEncapsulated(string $identifier, EncapsulatedPacket $packet, int $flags) : void;

	public function handleRaw(string $address, int $port, string $payload) : void;

	public function notifyACK(string $identifier, int $identifierACK) : void;

	public function handleOption(string $option, string $value) : void;

	public function updatePing(string $identifier, int $pingMS) : void;
}
