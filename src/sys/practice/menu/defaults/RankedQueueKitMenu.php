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
use sys\practice\kit\Kit;
use sys\practice\menu\Menu;
use sys\practice\utils\ArenaChestInventory;

class RankedQueueKitMenu extends Menu {

	/**
	 * Menu constructor.
	 * @param PracticeBase $plugin
	 */
	public function __construct(PracticeBase $plugin) {
		parent::__construct($plugin, $plugin->getKitManager()->getAllKitItems());
	}

	public function getInteraction(PracticePlayer $player, ArenaChestInventory $inventory, Item $item) {
		$name = TextFormat::clean($item->getCustomName());
		$kit = $this->getPlugin()->getKitManager()->getKitByName($name);
		if ($kit instanceof Kit) {
			if (!$player->isMobile()) {
				$queue = $this->getPlugin()->getQueueManager()->getQueue($kit, true, true);
			} else {
				$queue = $this->getPlugin()->getQueueManager()->getQueue($kit, true);
			}
			$queue->addPlayer($player);
			$player->getInventory()->clearAll();
			$player->getInventory()->setItem(0, Item::get(Item::PAPER)->setCustomName(TextFormat::WHITE . "Queue Info"));
			$player->getInventory()->setItem(8, Item::get(Item::REDSTONE)->setCustomName(TextFormat::RED . "Leave Queue"));
			$player->sendMessage(TextFormat::GREEN . "Successfully added to " . $kit->getName() . " queue!");
			$player->removeMenu();
			$inventory->onClose($player);
		}
	}
}