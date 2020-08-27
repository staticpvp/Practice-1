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
use sys\practice\form\DuelRequestForm;
use sys\practice\menu\Menu;
use sys\practice\utils\ArenaChestInventory;

class DuelKitMenu extends Menu {

	/** @var PracticePlayer */
	private $opponent;

	/**
	 * Menu constructor.
	 * @param PracticeBase $plugin
	 * @param PracticePlayer $opponent
	 */
	public function __construct(PracticeBase $plugin, PracticePlayer $opponent) {
		$this->opponent = $opponent;
		parent::__construct($plugin, $plugin->getKitManager()->getAllKitItems());
	}

	public function getOpponent() {
		return $this->opponent;
	}

	public function getInteraction(PracticePlayer $player, ArenaChestInventory $inventory, Item $item) {
		$kit = $this->getPlugin()->getKitManager()->getKitByName(TextFormat::clean($item->getCustomName()));
		if ($kit !== null) {
			if ($this->getOpponent()->inMatch()) return;
			$this->getOpponent()->sendForm(new DuelRequestForm($player, $kit));
			//TODO: Fix crashes w/ Chest UIs
//			if($this->getOpponent()->isMobile()) {
//			} else {
//				$menu = new DuelAcceptMenu($this->getPlugin(), $player, $kit);
//				$this->getOpponent()->addMenu($menu);
//				$this->getOpponent()->sendMenu(TextFormat::WHITE . "Duel Request from " . TextFormat::RED . $this->getOpponent()->getName() . TextFormat::WHITE . "!");
//			}
			$this->getOpponent()->setHasDuelRequest();
			$player->sendMessage(TextFormat::GREEN . "Successfully sent a duel request to " . $this->getOpponent()->getName() . "!");
			$player->removeMenu();
			$inventory->onClose($player);
		}
	}
}