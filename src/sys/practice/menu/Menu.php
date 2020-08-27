<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/19/2017
 * Time: 8:51 PM
 */

namespace sys\practice\menu;


use pocketmine\item\Item;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\utils\ArenaChestInventory;

abstract class Menu {

	/** @var PracticeBase */
	private $plugin;

	/** @var Item[] */
	private $items;

	/**
	 * Menu constructor.
	 * @param PracticeBase $plugin
	 * @param Item[] $items
	 */
	public function __construct(PracticeBase $plugin, array $items) {
		$this->plugin = $plugin;
		$this->items = $items;
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @return Item[]
	 */
	public function getItems(): array {
		return $this->items;
	}

	public abstract function getInteraction(PracticePlayer $player, ArenaChestInventory $inventory, Item $item);


}