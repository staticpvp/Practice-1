<?php

/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/21/2017
 * Time: 9:17 PM
 */

namespace sys\practice\menu\defaults;

use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\menu\Menu;
use sys\practice\utils\ArenaChestInventory;

class UnrankedQueueKitMenu extends Menu {

	/**
	 * Menu constructor.
	 * @param PracticeBase $plugin
	 */
	public function __construct(PracticeBase $plugin) {
		parent::__construct($plugin, $plugin->getKitManager()->getAllKitItems());
	}

	/**
	 * @param PracticePlayer $player
	 * @param ArenaChestInventory $inventory
	 * @param Item $item
	 */
	public function getInteraction(PracticePlayer $player, ArenaChestInventory $inventory, Item $item) {
		$kit = $this->getPlugin()->getKitManager()->getKitByName(TextFormat::clean($item->getCustomName()));
		if ($kit !== null) {
			if (!$player->isMobile()) {
				$queue = $this->getPlugin()->getQueueManager()->getQueue($kit, false, true);
			} else {
				$queue = $this->getPlugin()->getQueueManager()->getQueue($kit);
			}
			$queue->addPlayer($player);
			$player->getInventory()->clearAll();
			$player->getInventory()->setItem(0, Item::get(Item::PAPER)->setCustomName(TextFormat::WHITE . "Queue Info"));
			$player->getInventory()->setItem(8, Item::get(Item::REDSTONE)->setCustomName(TextFormat::RED . "Leave Queue"));
			$player->sendMessage(TextFormat::GREEN . "Successfully added to {$kit->getName()} queue!");
			$player->removeMenu();
			$inventory->onClose($player);
		}
	}
}