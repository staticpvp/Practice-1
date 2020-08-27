<?php
/**
 *
 * This file was created by Matt on 7/18/2017
 * Any attempts to copy, steal, or use this code
 * without permission will result in various consequences.
 *
 */

namespace sys\practice\interaction;


use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;

abstract class Interaction {

	/** @var Item[] */
	private $items = [];

	/** @var PracticeBase */
	private $plugin;

	/**
	 * Interaction constructor.
	 * @param PracticeBase $plugin
	 * @param array $items
	 */
	public function __construct(PracticeBase $plugin, array $items = []) {
		$this->plugin = $plugin;
		$this->items = $items;
	}

	/**
	 * @return Item[]
	 */
	public function getItems(): array {
		return $this->items;
	}

	/**
	 * @param Item $item
	 * @return bool
	 */
	public function exists(Item $item): bool {
		foreach($this->getItems() as $interactionItem) {
			if($item->getId() == $interactionItem->getId()) {
				if(TextFormat::clean($item->getCustomName()) == $interactionItem->getCustomName()) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @param PracticePlayer $player
	 * @param Item $item
	 * @return bool
	 *
	 * Returns true on success, false on failure
	 */
	abstract public function onInteract(PracticePlayer $player, Item $item): bool;


}