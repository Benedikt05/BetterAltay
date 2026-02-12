<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

final class DataDrivenScreenClosedReason{

	private function __construct(){
		//NOOP
	}

	public const PROGRAMMATIC_CLOSE = "programmaticclose";
	public const PROGRAMMATIC_CLOSE_ALL = "programaticcloseall";
	public const CLIENT_CANCELED = "clientcanceled";
	public const USER_BUSY = "userbusy";
	public const INVALID_FORM = "invalidform";
}