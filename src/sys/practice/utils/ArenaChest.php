<?php
/**
 *
 * This file was created by Matt on 7/19/2017
 * Any attempts to copy, steal, or use this code
 * without permission will result in various consequences.
 *
 */

namespace sys\practice\utils;


use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\tile\Chest;

class ArenaChest extends Chest {

	/**
	 * ArenaChest constructor.
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt) {
		parent::__construct($level, $nbt);
		$this->inventory = new ArenaChestInventory($this);
	}

	public function spawnToAll() {}

	/**
	 * @param Player $player
	 * @return bool
	 */
	public function spawnTo(Player $player): bool {
		return true;
	}

	public function saveNBT(): CompoundTag {
		/*
		 * Don't save the NBT, because that causes bugs and bugs are bad.
		 */
		return new CompoundTag();
	}

}