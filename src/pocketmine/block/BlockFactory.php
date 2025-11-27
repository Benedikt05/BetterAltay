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

namespace pocketmine\block;

use InvalidArgumentException;
use pocketmine\block\material\ColorType;
use pocketmine\block\material\OreType;
use pocketmine\block\material\SandstoneType;
use pocketmine\block\material\WoodType;
use pocketmine\level\Position;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use RuntimeException;
use function min;

/**
 * Manages block registration and instance creation
 */
class BlockFactory{

	/** @var Block[] */
	private static $fullList = [];
	/** @var bool[] */
	public static $solid = [];
	/** @var bool[] */
	public static $transparent = [];
	/** @var float[] */
	public static $hardness = [];
	/** @var int[] */
	public static $light = [];
	/** @var int[] */
	public static $lightFilter = [];
	/** @var float[] */
	public static $diffusesSkyLight = [];
	/** @var float[] */
	public static $blastResistance;

	/**
	 * Initializes the block factory. By default this is called only once on server start, however you may wish to use
	 * this if you need to reset the block factory back to its original defaults for whatever reason.
	 */
	public static function init() : void{
		if (!empty(self::$fullList)) {
			return;
		}

		self::registerBlock(new Air());
		self::registerBlock(new Stone());
		self::registerBlock(new Grass());
		self::registerBlock(new Dirt());
		self::registerBlock(new CoarseDirt());
		self::registerBlock(new Cobblestone());
		self::registerPlankBlocks();
		self::registerSaplingBlocks();
		self::registerBlock(new Bedrock());
		self::registerLiquidBlocks();
		self::registerSandBlocks();
		self::registerBlock(new Gravel());
		self::registerOreBlocks();
		self::registerLogBlocks();
		self::registerWoodBlocks();
		self::registerLeavesBlocks();
		self::registerBlock(new Sponge());
		self::registerGlassBlocks();
		self::registerBlock(new Lapis());
		//TODO: DISPENSER
		self::registerSandstoneBlocks();
		self::registerBlock(new NoteBlock());
		self::registerBlock(new Bed());
		self::registerBlock(new PoweredRail());
		self::registerBlock(new DetectorRail());
		//TODO: STICKY_PISTON
		self::registerBlock(new Cobweb());
		self::registerBlock(new TallGrass());
		self::registerBlock(new DeadBush());
		//TODO: PISTON
		//TODO: PISTONARMCOLLISION
		self::registerWoolBlocks();
		self::registerSignBlocks();
		self::registerWoodenDoors();
		self::registerBlock(new IronDoor());
		self::registerWoodenStairs();
		self::registerWoodenFences();
		self::registerShulkerBoxes();
//
//		self::registerBlock(new Dandelion());
//		self::registerBlock(new Flower());
		self::registerBlock(new BrownMushroom());
		self::registerBlock(new RedMushroom());
		self::registerBlock(new Gold());
		self::registerBlock(new Iron());
//		self::registerBlock(new Bricks());
		self::registerBlock(new TNT());
		self::registerBlock(new Bookshelf());
//		self::registerBlock(new MossyCobblestone());
		self::registerBlock(new Obsidian());
		self::registerBlock(new Torch());
		self::registerBlock(new Fire());
		self::registerBlock(new MonsterSpawner());
		self::registerBlock(new Chest());
//		//TODO: REDSTONE_WIRE
		self::registerBlock(new Diamond());
		self::registerBlock(new CraftingTable());
		self::registerBlock(new Wheat());
		self::registerBlock(new Farmland());
		self::registerBlock(new Furnace(BlockIds::FURNACE, 0, "Furnace", BlockIds::FURNACE));
		self::registerBlock(new Furnace(BlockIds::BLAST_FURNACE, 0, "Blast Furnace", BlockIds::BLAST_FURNACE));
		self::registerBlock(new BurningFurnace(BlockIds::LIT_FURNACE, 0, "Lit Furnace", BlockIds::FURNACE));
		self::registerBlock(new BurningFurnace(BlockIds::LIT_BLAST_FURNACE, 0, "Lit Blast Furnace", BlockIds::BLAST_FURNACE));
		self::registerBlock(new Ladder());
		self::registerBlock(new Rail());
		self::registerBlock(new CobblestoneStairs());
		self::registerBlock(new Lever());
		self::registerPressurePlateBlocks();
		self::registerBlock(new RedstoneTorchUnlit());
		self::registerBlock(new RedstoneTorch());
		self::registerBlock(new SoulTorch());
		self::registerBlock(new CopperTorch());
		self::registerBlock(new SnowLayer());
		self::registerBlock(new Ice());
		self::registerBlock(new Snow());
		self::registerBlock(new Cactus());
		self::registerBlock(new Clay());
		self::registerBlock(new Sugarcane());
		self::registerBlock(new Jukebox());
		self::registerBlock(new Pumpkin());
		self::registerBlock(new Netherrack());
		self::registerBlock(new SoulSand());
		self::registerBlock(new Glowstone());
//		self::registerBlock(new Portal());
		self::registerBlock(new LitPumpkin());
		self::registerBlock(new Cake());
		//TODO: REPEATER_BLOCK
		//TODO: POWERED_REPEATER
//		self::registerBlock(new InvisibleBedrock());
//		self::registerBlock(new Trapdoor());
//		self::registerBlock(new MonsterEgg());
//		self::registerBlock(new StoneBricks());
		self::registerBlock(new BrownMushroomBlock());
		self::registerBlock(new RedMushroomBlock());
		self::registerBlock(new IronBars());
		self::registerBlock(new Melon());
		self::registerBlock(new PumpkinStem());
		self::registerBlock(new MelonStem());
		self::registerBlock(new Vine());
		self::registerBlock(new BrickStairs());
		self::registerBlock(new StoneBrickStairs());
//		self::registerBlock(new Mycelium());
//		self::registerBlock(new WaterLily());
//		self::registerBlock(new NetherBrick(BlockIds::NETHER_BRICK_BLOCK, 0, "Nether Bricks"));
		self::registerBlock(new NetherBrickFence());
		self::registerBlock(new NetherBrickStairs());
//		self::registerBlock(new NetherWartPlant());
//		self::registerBlock(new EnchantingTable());
		self::registerBlock(new BrewingStand());
		self::registerBlock(new Cauldron());
//		self::registerBlock(new EndPortal());
//		self::registerBlock(new EndPortalFrame());
//		self::registerBlock(new EndStone());
		self::registerBlock(new DragonEgg());
//		self::registerBlock(new RedstoneLamp());
//		self::registerBlock(new LitRedstoneLamp());
		//TODO: DROPPER
//		self::registerBlock(new ActivatorRail());
		self::registerBlock(new CocoaBlock());
		self::registerBlock(new SandstoneStairs());
		self::registerBlock(new EnderChest());
		self::registerBlock(new TripwireHook());
		self::registerBlock(new Tripwire());
		self::registerBlock(new Emerald());
		//TODO: COMMAND_BLOCK
//		self::registerBlock(new Beacon());
//		self::registerBlock(new CobblestoneWall());
		self::registerBlock(new FlowerPot());
		self::registerBlock(new Carrot());
		self::registerBlock(new Potato());
		self::registerButtonBlocks();
		self::registerSkulls();
		self::registerBlock(new Anvil());
		self::registerBlock(new TrappedChest());
		//TODO: COMPARATOR_BLOCK
		//TODO: POWERED_COMPARATOR
//		self::registerBlock(new DaylightSensor());
//		self::registerBlock(new Redstone());
		self::registerBlock(new Hopper());
		self::registerBlock(new Quartz());
		self::registerBlock(new QuartzStairs());
		self::registerSlabBlocks();
		self::registerBlock(new Slime());
//
//		self::registerBlock(new IronTrapdoor());
//		self::registerBlock(new Prismarine());
//		self::registerBlock(new SeaLantern());
//		self::registerBlock(new HayBale());
//		self::registerBlock(new Carpet());
//		self::registerBlock(new Coal());
//		self::registerBlock(new PackedIce());
//		self::registerBlock(new DoublePlant());
		self::registerBlock(new StandingBanner());
		self::registerBlock(new WallBanner());
		//TODO: DAYLIGHT_DETECTOR_INVERTED
		self::registerBlock(new RedSandstoneStairs());
		self::registerFenceGate();
		//TODO: REPEATING_COMMAND_BLOCK
		//TODO: CHAIN_COMMAND_BLOCK
		self::registerBlock(new GrassPath());
		self::registerBlock(new ItemFrame());
		self::registerBlock(new GlowItemFrame());
		//TODO: CHORUS_FLOWER
		self::registerBlock(new Purpur());

		self::registerBlock(new PurpurStairs());

		self::registerBlock(new EndStoneBricks());
		//TODO: FROSTED_ICE
		self::registerBlock(new EndRod());
		//TODO: END_GATEWAY

		self::registerBlock(new Magma());
		self::registerBlock(new NetherWartBlock());
//		self::registerBlock(new NetherBrick(BlockIds::RED_NETHER_BRICK, 0, "Red Nether Bricks"));
		self::registerBlock(new BoneBlock());
		self::registerGlazedTerracotta();
		self::registerConcreteBlocks();
		self::registerTerracotta();

		//TODO: CHORUS_PLANT

		self::registerBlock(new Podzol());
		self::registerBlock(new Beetroot());
//		self::registerBlock(new Stonecutter());
//		self::registerBlock(new GlowingObsidian());
//		self::registerBlock(new NetherReactor());
//		self::registerBlock(new InfoUpdate(BlockIds::INFO_UPDATE, 0, "update!"));
//		self::registerBlock(new InfoUpdate(BlockIds::INFO_UPDATE2, 0, "ate!upd"));
		//TODO: MOVINGBLOCK
		//TODO: OBSERVER
		//TODO: STRUCTURE_BLOCK

		self::registerBlock(new Reserved6(BlockIds::RESERVED6, 0, "reserved6"));
		self::registerBlock(new UnknownBlock());

	}

	public static function isInit() : bool{
		return self::$fullList !== null;
	}

	/**
	 * Registers a block type into the index. Plugins may use this method to register new block types or override
	 * existing ones.
	 *
	 * NOTE: If you are registering a new block type, you will need to add it to the creative inventory yourself - it
	 * will not automatically appear there.
	 *
	 * @param bool $override Whether to override existing registrations
	 *
	 * @throws RuntimeException if something attempted to override an already-registered block without specifying the
	 * $override parameter.
	 */
	public static function registerBlock(Block $block, bool $override = false) : void{
		$id = $block->getId();

		if(!$override and self::isRegistered($id)){
			throw new RuntimeException("Trying to overwrite an already registered block " . $id);
		}

		self::$fullList[$id] = clone $block;

		self::$solid[$id] = $block->isSolid();
		self::$transparent[$id] = $block->isTransparent();
		self::$hardness[$id] = $block->getHardness();
		self::$light[$id] = $block->getLightLevel();
		self::$lightFilter[$id] = min(15, $block->getLightFilter() + 1); //opacity plus 1 standard light filter
		self::$diffusesSkyLight[$id] = $block->diffusesSkyLight();
		self::$blastResistance[$id] = $block->getBlastResistance();
	}

	/**
	 * Returns a new Block instance with the specified ID, meta and position.
	 */
	public static function get(string $id, int $meta = 0, Position $pos = null) : Block{
		if($meta < 0 or $meta > 0x40){
			throw new InvalidArgumentException("Block meta value $meta is out of bounds");
		}

		try{
			$block = clone (self::$fullList[$id] ?? self::$fullList[BlockIds::UNKNOWN]);
			$block->setDamage($meta);
		}catch(RuntimeException $e){
			throw new InvalidArgumentException("Block ID $id is out of bounds");
		}

		if($pos !== null){
			$block->x = $pos->getFloorX();
			$block->y = $pos->getFloorY();
			$block->z = $pos->getFloorZ();
			$block->level = $pos->level;
		}

		return $block;
	}

	/**
	 * @internal
	 * @var []Block
	 */
	public static function getBlockStatesArray() : array{
		return self::$fullList;
	}

	/**
	 * Returns whether a specified block ID is already registered in the block factory.
	 */
	public static function isRegistered(string $id) : bool{
		return isset(self::$fullList[$id]);
	}

	/**
	 * @internal
	 * @deprecated
	 */
	public static function toStaticRuntimeId(string $id, int $meta = 0) : int{
		return RuntimeBlockMapping::toRuntimeId($id, $meta);
	}

	/**
	 * @return int[] [id, meta]
	 * @internal
	 *
	 * @deprecated
	 */
	public static function fromStaticRuntimeId(int $runtimeId) : array{
		return RuntimeBlockMapping::fromRuntimeId($runtimeId);
	}

	private static function registerPlankBlocks() : void{
		foreach(WoodType::values() as $type){
			self::registerBlock(new Planks($type));
		}
		self::registerBlock(new Planks(new WoodType("bamboo", "Bamboo")));
	}

	private static function registerSaplingBlocks() : void{
		foreach(WoodType::values() as $type){
			self::registerBlock(new Sapling($type));
		}
		self::registerBlock(new Sapling(new WoodType("bamboo", "Bamboo")));
	}

	private static function registerLiquidBlocks() : void{
		self::registerBlock(new Water());
		self::registerBlock(new FlowinglWater());
		self::registerBlock(new Lava());
		self::registerBlock(new FlowingLava());
	}

	private static function registerSandBlocks() : void{
		self::registerBlock(new Sand());
		self::registerBlock(new Sand(BlockIds::RED_SAND));
	}

	private static function registerOreBlocks() : void{
		foreach(OreType::values() as $type){
			self::registerBlock(new Ore($type));
			self::registerBlock(new DeepslateOre($type));
		}
	}

	private static function registerLogBlocks() : void{
		foreach(WoodType::values() as $type){
			self::registerBlock(new Log($type));
		}
	}

	private static function registerWoodBlocks() : void{
		foreach(WoodType::values() as $type){
			self::registerBlock(new Wood($type));
		}
	}

	private static function registerLeavesBlocks() : void{
		foreach(WoodType::values() as $type){
			self::registerBlock(new Leaves($type));
		}
		self::registerBlock(new Leaves(new WoodType("Azalea", "Azalea")));
	}

	private static function registerGlassBlocks() : void{
		self::registerBlock(new Glass());
		self::registerBlock(new GlassPane());
		foreach(ColorType::values() as $type){
			self::registerBlock(new StainedGlass($type));
			self::registerBlock(new StainedGlassPane($type));
			self::registerBlock(new HardenedStainedGlass($type));
		}
	}

	private static function registerSandstoneBlocks() : void{
		foreach(SandstoneType::values() as $type){
			self::registerBlock(new Sandstone($type));
			self::registerBlock(new RedSandstone($type));
		}
	}

	private static function registerWoolBlocks() : void{
		foreach(ColorType::values() as $type){
			self::registerBlock(new Wool($type));
		}
	}

	private static function registerSignBlocks() : void{
		foreach(WoodType::values() as $type){
			self::registerBlock(new SignPost($type));
			self::registerBlock(new WallSign($type));
		}
		$bamboo = new WoodType("bamboo", "Bamboo");
		self::registerBlock(new SignPost($bamboo));
		self::registerBlock(new WallSign($bamboo));
	}

	private static function registerWoodenDoors() : void{
		foreach(WoodType::values() as $type){
			self::registerBlock(new WoodenDoor($type));
		}
		self::registerBlock(new WoodenDoor(new WoodType("bamboo", "Bamboo")));
	}

	private static function registerWoodenStairs() : void{
		foreach(WoodType::values() as $type){
			self::registerBlock(new WoodenStairs("minecraft:" . $type->getType() . "_stairs", 0, $type->getName() . " Stairs"));
		}
		self::registerBlock(new WoodenStairs(BlockIds::BAMBOO_STAIRS));
	}

	private static function registerWoodenFences() : void {
		foreach(WoodType::values() as $type){
			self::registerBlock(new WoodenFence($type));
		}
		self::registerBlock(new WoodenFence(new WoodType("bamboo", "Bamboo")));
	}

	private static function registerShulkerBoxes() : void{
		foreach(ColorType::values() as $type){
			self::registerBlock(new ShulkerBox($type));
		}
		self::registerBlock(new ShulkerBox(new ColorType("undyed", "")));
	}

	private static function registerSkulls() : void{
		self::registerBlock(new Skull(BlockIds::SKELETON_SKULL, 0 , "Skeleton Skull"));
		self::registerBlock(new Skull(BlockIds::WITHER_SKELETON_SKULL, 0 , "Wither Skeleton Skull"));
		self::registerBlock(new Skull(BlockIds::ZOMBIE_HEAD, 0 , "Zombie Skull"));
		self::registerBlock(new Skull(BlockIds::PLAYER_HEAD, 0 , "Skull"));
		self::registerBlock(new Skull(BlockIds::CREEPER_HEAD, 0 , "Creeper Skull"));
		self::registerBlock(new Skull(BlockIds::DRAGON_HEAD, 0 , "Dragon Skull"));
	}

	private static function registerFenceGate() : void{
		foreach(WoodType::values() as $type){
			self::registerBlock(new FenceGate($type));
		}
		self::registerBlock(new FenceGate(new WoodType("bamboo", "Bamboo")));
	}

	private static function registerConcreteBlocks() : void{
		foreach(ColorType::values() as $type){
			self::registerBlock(new Concrete($type));
			self::registerBlock(new ConcretePowder($type));
		}
	}

	private static function registerTerracotta() : void{
		foreach(ColorType::values() as $type){
			self::registerBlock(new Terracotta($type));
		}
	}

	private static function registerGlazedTerracotta() : void{
		self::registerBlock(new GlazedTerracotta(BlockIds::PURPLE_GLAZED_TERRACOTTA, 0, "Purple Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::WHITE_GLAZED_TERRACOTTA, 0, "White Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::ORANGE_GLAZED_TERRACOTTA, 0, "Orange Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::MAGENTA_GLAZED_TERRACOTTA, 0, "Magenta Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::LIGHT_BLUE_GLAZED_TERRACOTTA, 0, "Light Blue Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::YELLOW_GLAZED_TERRACOTTA, 0, "Yellow Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::LIME_GLAZED_TERRACOTTA, 0, "Lime Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::PINK_GLAZED_TERRACOTTA, 0, "Pink Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::GRAY_GLAZED_TERRACOTTA, 0, "Grey Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::SILVER_GLAZED_TERRACOTTA, 0, "Light Grey Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::CYAN_GLAZED_TERRACOTTA, 0, "Cyan Glazed Terracotta"));

		self::registerBlock(new GlazedTerracotta(BlockIds::BLUE_GLAZED_TERRACOTTA, 0, "Blue Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::BROWN_GLAZED_TERRACOTTA, 0, "Brown Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::GREEN_GLAZED_TERRACOTTA, 0, "Green Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::RED_GLAZED_TERRACOTTA, 0, "Red Glazed Terracotta"));
		self::registerBlock(new GlazedTerracotta(BlockIds::BLACK_GLAZED_TERRACOTTA, 0, "Black Glazed Terracotta"));
	}

	private static function registerPressurePlateBlocks() : void{
		self::registerBlock(new StonePressurePlate());
		self::registerBlock(new WeightedPressurePlateLight());
		self::registerBlock(new WeightedPressurePlateHeavy());
		foreach(WoodType::values() as $type){
			self::registerBlock(new WoodenPressurePlate($type));
		}
	}

	private static function registerSlabBlocks() : void{
		self::registerBlock(new StoneSlab());
		self::registerBlock(new DoubleStoneSlab());

		foreach(WoodType::values() as $type){
			self::registerBlock(new WoodenSlab($type));
			self::registerBlock(new DoubleWoodenSlab($type));
		}

		$bamboo = new WoodType("bamboo", "Bamboo");
		self::registerBlock(new WoodenSlab($bamboo));
		self::registerBlock(new DoubleWoodenSlab($bamboo));
	}

	private static function registerButtonBlocks() : void{
		self::registerBlock(new StoneButton());
		foreach(WoodType::values() as $type){
			self::registerBlock(new WoodenButton($type));
		}
	}
}
