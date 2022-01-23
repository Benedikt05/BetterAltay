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

namespace pocketmine\plugin;

use pocketmine\command\PluginCommand;
use pocketmine\command\SimpleCommandMap;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerList;
use pocketmine\event\Listener;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\plugin\PluginEnableEvent;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\permission\Permissible;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\Server;
use pocketmine\timings\TimingsHandler;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;
use function array_intersect;
use function array_map;
use function array_merge;
use function array_pad;
use function class_exists;
use function count;
use function dirname;
use function explode;
use function file_exists;
use function get_class;
use function gettype;
use function implode;
use function in_array;
use function is_a;
use function is_array;
use function is_bool;
use function is_dir;
use function is_string;
use function is_subclass_of;
use function iterator_to_array;
use function mb_strtoupper;
use function mkdir;
use function shuffle;
use function stripos;
use function strpos;
use function strtolower;
use const DIRECTORY_SEPARATOR;

/**
 * Manages all the plugins
 */
class PluginManager{

	/** @var Server */
	private $server;

	/** @var SimpleCommandMap */
	private $commandMap;

	/** @var Plugin[] */
	protected $plugins = [];

	/** @var Plugin[] */
	protected $enabledPlugins = [];

	/**
	 * @var PluginLoader[]
	 * @phpstan-var array<class-string<PluginLoader>, PluginLoader>
	 */
	protected $fileAssociations = [];

	/** @var string|null */
	private $pluginDataDirectory;

	public function __construct(Server $server, SimpleCommandMap $commandMap, ?string $pluginDataDirectory){
		$this->server = $server;
		$this->commandMap = $commandMap;
		$this->pluginDataDirectory = $pluginDataDirectory;
		if($this->pluginDataDirectory !== null){
			if(!file_exists($this->pluginDataDirectory)){
				@mkdir($this->pluginDataDirectory, 0777, true);
			}elseif(!is_dir($this->pluginDataDirectory)){
				throw new \RuntimeException("Plugin data path $this->pluginDataDirectory exists and is not a directory");
			}
		}
	}

	/**
	 * @return null|Plugin
	 */
	public function getPlugin(string $name){
		if(isset($this->plugins[$name])){
			return $this->plugins[$name];
		}

		return null;
	}

	public function registerInterface(PluginLoader $loader) : void{
		$this->fileAssociations[get_class($loader)] = $loader;
	}

	/**
	 * @return Plugin[]
	 */
	public function getPlugins() : array{
		return $this->plugins;
	}

	private function getDataDirectory(string $pluginPath, string $pluginName) : string{
		if($this->pluginDataDirectory !== null){
			return $this->pluginDataDirectory . $pluginName;
		}
		return dirname($pluginPath) . DIRECTORY_SEPARATOR . $pluginName;
	}

	/**
	 * @param PluginLoader[] $loaders
	 */
	public function loadPlugin(string $path, array $loaders = null) : ?Plugin{
		foreach($loaders ?? $this->fileAssociations as $loader){
			if($loader->canLoadPlugin($path)){
				$description = $loader->getPluginDescription($path);
				if($description instanceof PluginDescription){
					$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.load", [$description->getFullName()]));
					try{
						$description->checkRequiredExtensions();
					}catch(PluginException $ex){
						$this->server->getLogger()->error($ex->getMessage());
						return null;
					}

					$dataFolder = $this->getDataDirectory($path, $description->getName());
					if(file_exists($dataFolder) and !is_dir($dataFolder)){
						$this->server->getLogger()->error("Projected dataFolder '" . $dataFolder . "' for " . $description->getName() . " exists and is not a directory");
						return null;
					}
					if(!file_exists($dataFolder)){
						mkdir($dataFolder, 0777, true);
					}

					$prefixed = $loader->getAccessProtocol() . $path;
					$loader->loadPlugin($prefixed);

					$mainClass = $description->getMain();
					if(!class_exists($mainClass, true)){
						$this->server->getLogger()->error("Main class for plugin " . $description->getName() . " not found");
						return null;
					}
					if(!is_a($mainClass, Plugin::class, true)){
						$this->server->getLogger()->error("Main class for plugin " . $description->getName() . " is not an instance of " . Plugin::class);
						return null;
					}

					try{
						/**
						 * @var Plugin $plugin
						 * @see Plugin::__construct()
						 */
						$plugin = new $mainClass($loader, $this->server, $description, $dataFolder, $prefixed);
						$plugin->onLoad();
						$this->plugins[$plugin->getDescription()->getName()] = $plugin;

						$pluginCommands = $this->parseYamlCommands($plugin);

						if(count($pluginCommands) > 0){
							$this->commandMap->registerAll($plugin->getDescription()->getName(), $pluginCommands);
						}

						return $plugin;
					}catch(\Throwable $e){
						$this->server->getLogger()->logException($e);
						return null;
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param string[]|null $newLoaders
	 * @phpstan-param list<class-string<PluginLoader>> $newLoaders
	 *
	 * @return Plugin[]
	 */
	public function loadPlugins(string $directory, array $newLoaders = null){
		if(!is_dir($directory)){
			return [];
		}

		$plugins = [];
		$loadedPlugins = [];
		$dependencies = [];
		$softDependencies = [];
		if(is_array($newLoaders)){
			$loaders = [];
			foreach($newLoaders as $key){
				if(isset($this->fileAssociations[$key])){
					$loaders[$key] = $this->fileAssociations[$key];
				}
			}
		}else{
			$loaders = $this->fileAssociations;
		}

		$files = iterator_to_array(new \FilesystemIterator($directory, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS));
		shuffle($files); //this prevents plugins implicitly relying on the filesystem name order when they should be using dependency properties
		foreach($loaders as $loader){
			foreach($files as $file){
				if(!is_string($file)) throw new AssumptionFailedError("FilesystemIterator current should be string when using CURRENT_AS_PATHNAME");
				if(!$loader->canLoadPlugin($file)){
					continue;
				}
				try{
					$description = $loader->getPluginDescription($file);
					if($description === null){
						continue;
					}

					$name = $description->getName();
					if(stripos($name, "pocketmine") !== false or stripos($name, "minecraft") !== false or stripos($name, "mojang") !== false){
						$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [$name, "%pocketmine.plugin.restrictedName"]));
						continue;
					}elseif(strpos($name, " ") !== false){
						$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.plugin.spacesDiscouraged", [$name]));
					}

					if(isset($plugins[$name]) or $this->getPlugin($name) instanceof Plugin){
						$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.duplicateError", [$name]));
						continue;
					}

					if(!$this->isCompatibleApi(...$description->getCompatibleApis())){
						$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [
							$name,
							$this->server->getLanguage()->translateString("%pocketmine.plugin.incompatibleAPI", [implode(", ", $description->getCompatibleApis())])
						]));
						continue;
					}

					if(count($description->getCompatibleOperatingSystems()) > 0 and !in_array(Utils::getOS(), $description->getCompatibleOperatingSystems(), true)) {
						$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [
							$name,
							$this->server->getLanguage()->translateString("%pocketmine.plugin.incompatibleOS", [implode(", ", $description->getCompatibleOperatingSystems())])
						]));
						continue;
					}

					if(count($pluginMcpeProtocols = $description->getCompatibleMcpeProtocols()) > 0){
						$serverMcpeProtocols = [ProtocolInfo::CURRENT_PROTOCOL];
						if(count(array_intersect($pluginMcpeProtocols, $serverMcpeProtocols)) === 0){
							$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [
								$name,
								$this->server->getLanguage()->translateString("%pocketmine.plugin.incompatibleProtocol", [implode(", ", $pluginMcpeProtocols)])
							]));
							continue;
						}
					}

					$plugins[$name] = $file;

					$softDependencies[$name] = array_merge($softDependencies[$name] ?? [], $description->getSoftDepend());
					$dependencies[$name] = $description->getDepend();

					foreach($description->getLoadBefore() as $before){
						if(isset($softDependencies[$before])){
							$softDependencies[$before][] = $name;
						}else{
							$softDependencies[$before] = [$name];
						}
					}
				}catch(\Throwable $e){
					$this->server->getLogger()->error($this->server->getLanguage()->translateString("pocketmine.plugin.fileError", [$file, $directory, $e->getMessage()]));
					$this->server->getLogger()->logException($e);
				}
			}
		}

		while(count($plugins) > 0){
			$loadedThisLoop = 0;
			foreach($plugins as $name => $file){
				if(isset($dependencies[$name])){
					foreach($dependencies[$name] as $key => $dependency){
						if(isset($loadedPlugins[$dependency]) or $this->getPlugin($dependency) instanceof Plugin){
							unset($dependencies[$name][$key]);
						}elseif(!isset($plugins[$dependency])){
							$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [
								$name,
								$this->server->getLanguage()->translateString("%pocketmine.plugin.unknownDependency", [$dependency])
							]));
							unset($plugins[$name]);
							continue 2;
						}
					}

					if(count($dependencies[$name]) === 0){
						unset($dependencies[$name]);
					}
				}

				if(isset($softDependencies[$name])){
					foreach($softDependencies[$name] as $key => $dependency){
						if(isset($loadedPlugins[$dependency]) or $this->getPlugin($dependency) instanceof Plugin){
							$this->server->getLogger()->debug("Successfully resolved soft dependency \"$dependency\" for plugin \"$name\"");
							unset($softDependencies[$name][$key]);
						}elseif(!isset($plugins[$dependency])){
							//this dependency is never going to be resolved, so don't bother trying
							$this->server->getLogger()->debug("Skipping resolution of missing soft dependency \"$dependency\" for plugin \"$name\"");
							unset($softDependencies[$name][$key]);
						}else{
							$this->server->getLogger()->debug("Deferring resolution of soft dependency \"$dependency\" for plugin \"$name\" (found but not loaded yet)");
						}
					}

					if(count($softDependencies[$name]) === 0){
						unset($softDependencies[$name]);
					}
				}

				if(!isset($dependencies[$name]) and !isset($softDependencies[$name])){
					unset($plugins[$name]);
					$loadedThisLoop++;
					if(($plugin = $this->loadPlugin($file, $loaders)) instanceof Plugin){
						$loadedPlugins[$name] = $plugin;
					}else{
						$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.genericLoadError", [$name]));
					}
				}
			}

			if($loadedThisLoop === 0){
				//No plugins loaded :(
				foreach($plugins as $name => $file){
					$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.loadError", [$name, "%pocketmine.plugin.circularDependency"]));
				}
				$plugins = [];
			}
		}

		return $loadedPlugins;
	}

	/**
	 * Returns whether a specified API version string is considered compatible with the server's API version.
	 */
	public function isCompatibleApi(string ...$versions) : bool{
		$serverString = $this->server->getApiVersion();
		$serverApi = array_pad(explode("-", $serverString, 2), 2, "");
		$serverNumbers = array_map("\intval", explode(".", $serverApi[0]));

		foreach($versions as $version){
			//Format: majorVersion.minorVersion.patch (3.0.0)
			//    or: majorVersion.minorVersion.patch-devBuild (3.0.0-alpha1)
			if($version !== $serverString){
				$pluginApi = array_pad(explode("-", $version, 2), 2, ""); //0 = version, 1 = suffix (optional)

				if(mb_strtoupper($pluginApi[1]) !== mb_strtoupper($serverApi[1])){ //Different release phase (alpha vs. beta) or phase build (alpha.1 vs alpha.2)
					continue;
				}

				$pluginNumbers = array_map("\intval", array_pad(explode(".", $pluginApi[0]), 3, "0")); //plugins might specify API like "3.0" or "3"

				if($pluginNumbers[0] !== $serverNumbers[0]){ //Completely different API version
					continue;
				}

				if($pluginNumbers[1] > $serverNumbers[1]){ //If the plugin requires new API features, being backwards compatible
					continue;
				}

				if($pluginNumbers[1] === $serverNumbers[1] and $pluginNumbers[2] > $serverNumbers[2]){ //If the plugin requires bug fixes in patches, being backwards compatible
					continue;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @deprecated
	 * @see PermissionManager::getPermission()
	 *
	 * @return null|Permission
	 */
	public function getPermission(string $name){
		return PermissionManager::getInstance()->getPermission($name);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::addPermission()
	 */
	public function addPermission(Permission $permission) : bool{
		return PermissionManager::getInstance()->addPermission($permission);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::removePermission()
	 *
	 * @param string|Permission $permission
	 *
	 * @return void
	 */
	public function removePermission($permission){
		PermissionManager::getInstance()->removePermission($permission);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::getDefaultPermissions()
	 *
	 * @return Permission[]
	 */
	public function getDefaultPermissions(bool $op) : array{
		return PermissionManager::getInstance()->getDefaultPermissions($op);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::recalculatePermissionDefaults()
	 *
	 * @return void
	 */
	public function recalculatePermissionDefaults(Permission $permission){
		PermissionManager::getInstance()->recalculatePermissionDefaults($permission);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::subscribeToPermission()
	 *
	 * @return void
	 */
	public function subscribeToPermission(string $permission, Permissible $permissible){
		PermissionManager::getInstance()->subscribeToPermission($permission, $permissible);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::unsubscribeFromPermission()
	 *
	 * @return void
	 */
	public function unsubscribeFromPermission(string $permission, Permissible $permissible){
		PermissionManager::getInstance()->unsubscribeFromPermission($permission, $permissible);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::unsubscribeFromAllPermissions()
	 */
	public function unsubscribeFromAllPermissions(Permissible $permissible) : void{
		PermissionManager::getInstance()->unsubscribeFromAllPermissions($permissible);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::getPermissionSubscriptions()
	 *
	 * @return array|Permissible[]
	 */
	public function getPermissionSubscriptions(string $permission) : array{
		return PermissionManager::getInstance()->getPermissionSubscriptions($permission);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::subscribeToDefaultPerms()
	 *
	 * @return void
	 */
	public function subscribeToDefaultPerms(bool $op, Permissible $permissible){
		PermissionManager::getInstance()->subscribeToDefaultPerms($op, $permissible);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::unsubscribeFromDefaultPerms()
	 *
	 * @return void
	 */
	public function unsubscribeFromDefaultPerms(bool $op, Permissible $permissible){
		PermissionManager::getInstance()->unsubscribeFromDefaultPerms($op, $permissible);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::getDefaultPermSubscriptions()
	 *
	 * @return Permissible[]
	 */
	public function getDefaultPermSubscriptions(bool $op) : array{
		return PermissionManager::getInstance()->getDefaultPermSubscriptions($op);
	}

	/**
	 * @deprecated
	 * @see PermissionManager::getPermissions()
	 *
	 * @return Permission[]
	 */
	public function getPermissions() : array{
		return PermissionManager::getInstance()->getPermissions();
	}

	public function isPluginEnabled(Plugin $plugin) : bool{
		return isset($this->plugins[$plugin->getDescription()->getName()]) and $plugin->isEnabled();
	}

	/**
	 * @return void
	 */
	public function enablePlugin(Plugin $plugin){
		if(!$plugin->isEnabled()){
			try{
				$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.enable", [$plugin->getDescription()->getFullName()]));

				$permManager = PermissionManager::getInstance();
				foreach($plugin->getDescription()->getPermissions() as $perm){
					$permManager->addPermission($perm);
				}
				$plugin->getScheduler()->setEnabled(true);
				$plugin->setEnabled(true);

				$this->enabledPlugins[$plugin->getDescription()->getName()] = $plugin;

				(new PluginEnableEvent($plugin))->call();
			}catch(\Throwable $e){
				$this->server->getLogger()->logException($e);
				$this->disablePlugin($plugin);
			}
		}
	}

	/**
	 * @return PluginCommand[]
	 */
	protected function parseYamlCommands(Plugin $plugin) : array{
		$pluginCmds = [];

		foreach($plugin->getDescription()->getCommands() as $key => $data){
			if(strpos($key, ":") !== false){
				$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.commandError", [$key, $plugin->getDescription()->getFullName()]));
				continue;
			}
			if(is_array($data)){
				$newCmd = new PluginCommand($key, $plugin);
				if(isset($data["description"])){
					$newCmd->setDescription($data["description"]);
				}

				if(isset($data["usage"])){
					$newCmd->setUsage($data["usage"]);
				}

				if(isset($data["aliases"]) and is_array($data["aliases"])){
					$aliasList = [];
					foreach($data["aliases"] as $alias){
						if(strpos($alias, ":") !== false){
							$this->server->getLogger()->critical($this->server->getLanguage()->translateString("pocketmine.plugin.aliasError", [$alias, $plugin->getDescription()->getFullName()]));
							continue;
						}
						$aliasList[] = $alias;
					}

					$newCmd->setAliases($aliasList);
				}

				if(isset($data["permission"])){
					if(is_bool($data["permission"])){
						$newCmd->setPermission($data["permission"] ? "true" : "false");
					}elseif(is_string($data["permission"])){
						$newCmd->setPermission($data["permission"]);
					}else{
						throw new \InvalidArgumentException("Permission must be a string or boolean, " . gettype($data["permission"]) . " given");
					}
				}

				if(isset($data["permission-message"])){
					$newCmd->setPermissionMessage($data["permission-message"]);
				}

				$pluginCmds[] = $newCmd;
			}
		}

		return $pluginCmds;
	}

	/**
	 * @return void
	 */
	public function disablePlugins(){
		foreach($this->getPlugins() as $plugin){
			$this->disablePlugin($plugin);
		}
	}

	/**
	 * @return void
	 */
	public function disablePlugin(Plugin $plugin){
		if($plugin->isEnabled()){
			$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.disable", [$plugin->getDescription()->getFullName()]));
			(new PluginDisableEvent($plugin))->call();

			unset($this->enabledPlugins[$plugin->getDescription()->getName()]);

			try{
				$plugin->setEnabled(false);
			}catch(\Throwable $e){
				$this->server->getLogger()->logException($e);
			}
			$plugin->getScheduler()->shutdown();
			HandlerList::unregisterAll($plugin);
			$permManager = PermissionManager::getInstance();
			foreach($plugin->getDescription()->getPermissions() as $perm){
				$permManager->removePermission($perm);
			}
		}
	}

	public function tickSchedulers(int $currentTick) : void{
		foreach($this->enabledPlugins as $p){
			$p->getScheduler()->mainThreadHeartbeat($currentTick);
		}
	}

	/**
	 * @return void
	 */
	public function clearPlugins(){
		$this->disablePlugins();
		$this->plugins = [];
		$this->enabledPlugins = [];
		$this->fileAssociations = [];
	}

	/**
	 * Calls an event
	 *
	 * @deprecated
	 * @see Event::call()
	 *
	 * @return void
	 */
	public function callEvent(Event $event){
		$event->call();
	}

	/**
	 * Registers all the events in the given Listener class
	 *
	 * @throws PluginException
	 */
	public function registerEvents(Listener $listener, Plugin $plugin) : void{
		if(!$plugin->isEnabled()){
			throw new PluginException("Plugin attempted to register " . get_class($listener) . " while not enabled");
		}

		$reflection = new \ReflectionClass(get_class($listener));
		foreach($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
			if(!$method->isStatic() and $method->getDeclaringClass()->implementsInterface(Listener::class)){
				$tags = Utils::parseDocComment((string) $method->getDocComment());
				if(isset($tags["notHandler"])){
					continue;
				}

				$parameters = $method->getParameters();
				if(count($parameters) !== 1){
					continue;
				}

				$handlerClosure = $method->getClosure($listener);
				if($handlerClosure === null) throw new AssumptionFailedError("This should never happen");

				try{
					$paramType = $parameters[0]->getType();
					//isBuiltin() returns false for builtin classes ..................
					if($paramType instanceof \ReflectionNamedType && !$paramType->isBuiltin()){
						/** @phpstan-var class-string $paramClass */
						$paramClass = $paramType->getName();
						$eventClass = new \ReflectionClass($paramClass);
					}else{
						$eventClass = null;
					}
				}catch(\ReflectionException $e){ //class doesn't exist
					if(isset($tags["softDepend"]) && !isset($this->plugins[$tags["softDepend"]])){
						$this->server->getLogger()->debug("Not registering @softDepend listener " . Utils::getNiceClosureName($handlerClosure) . "() because plugin \"" . $tags["softDepend"] . "\" not found");
						continue;
					}

					throw $e;
				}
				if($eventClass === null or !$eventClass->isSubclassOf(Event::class)){
					continue;
				}

				try{
					$priority = isset($tags["priority"]) ? EventPriority::fromString($tags["priority"]) : EventPriority::NORMAL;
				}catch(\InvalidArgumentException $e){
					throw new PluginException("Event handler " . Utils::getNiceClosureName($handlerClosure) . "() declares invalid/unknown priority \"" . $tags["priority"] . "\"");
				}

				$ignoreCancelled = false;
				if(isset($tags["ignoreCancelled"])){
					switch(strtolower($tags["ignoreCancelled"])){
						case "true":
						case "":
							$ignoreCancelled = true;
							break;
						case "false":
							$ignoreCancelled = false;
							break;
						default:
							throw new PluginException("Event handler " . Utils::getNiceClosureName($handlerClosure) . "() declares invalid @ignoreCancelled value \"" . $tags["ignoreCancelled"] . "\"");
					}
				}

				$this->registerEvent($eventClass->getName(), $listener, $priority, new MethodEventExecutor($method->getName()), $plugin, $ignoreCancelled);
			}
		}
	}

	/**
	 * @param string $event Class name that extends Event
	 * @phpstan-param class-string<Event> $event
	 *
	 * @throws PluginException
	 */
	public function registerEvent(string $event, Listener $listener, int $priority, EventExecutor $executor, Plugin $plugin, bool $ignoreCancelled = false) : void{
		if(!is_subclass_of($event, Event::class)){
			throw new PluginException($event . " is not an Event");
		}

		if(!$plugin->isEnabled()){
			throw new PluginException("Plugin attempted to register " . $event . " while not enabled");
		}

		$timings = new TimingsHandler("Plugin: " . $plugin->getDescription()->getFullName() . " Event: " . get_class($listener) . "::" . ($executor instanceof MethodEventExecutor ? $executor->getMethod() : "???") . "(" . (new \ReflectionClass($event))->getShortName() . ")");

		$this->getEventListeners($event)->register(new RegisteredListener($listener, $executor, $priority, $plugin, $ignoreCancelled, $timings));
	}

	private function getEventListeners(string $event) : HandlerList{
		$list = HandlerList::getHandlerListFor($event);
		if($list === null){
			throw new PluginException("Abstract events not declaring @allowHandle cannot be handled (tried to register listener for $event)");
		}
		return $list;
	}
}
