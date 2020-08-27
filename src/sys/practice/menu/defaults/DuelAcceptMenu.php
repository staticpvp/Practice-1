<?php

/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/21/2017
 * Time: 9:17 PM
 */

namespace sys\practice\menu\defaults;


use pocketmine\item\Item;
use pocketmine\tile\Skull;
use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\kit\Kit;
use sys\practice\menu\Menu;
use sys\practice\utils\ArenaChestInventory;

class DuelAcceptMenu extends Menu {

	/** @var PracticePlayer */
	private $opponent = null;

	/** @var Kit */
	private $kit = null;

	/**
	 * Menu constructor.
	 * @param PracticeBase $plugin
	 * @param PracticePlayer $opponent
	 * @param Kit $kit
	 */
	public function __construct(PracticeBase $plugin, PracticePlayer $opponent, Kit $kit) {
		$this->opponent = $opponent;
		$this->kit = $kit;
		$items = $this->itemInit();
		parent::__construct($plugin, $items);
	}

	private function itemInit() {
		$itemString = "kxxxgxxxhxxxxxxxxxxxxxrxxxx";
		$itemArray = str_split($itemString);
		$items = [];
		foreach ($itemArray as $item) {
			$id = 0;
			$meta = 0;
			switch ($item) {
				case "k":
					$id = Item::IRON_SWORD;
					$name = TextFormat::GREEN . $this->getKit()->getName();
					break;
				case "x":
					$id = Item::AIR;
					break;
				case "g":
					$id = Item::STAINED_HARDENED_CLAY;
					$meta = 13;
					$name = TextFormat::GREEN . "Accept " . $this->getOpponent()->getName() . "'s request!";
					break;
				case "h":
					$id = Item::SKULL;
					$meta = Skull::TYPE_HUMAN;
					$name = TextFormat::WHITE . "Request From: " . TextFormat::RED . $this->getOpponent()->getName();
					break;
				case "r":
					$id = Item::STAINED_HARDENED_CLAY;
					$meta = 14;
					$name = TextFormat::RED . "Deny " . $this->getOpponent()->getName() . "'s request!";
					break;
			}
			$i = Item::get($id, $meta);
			if (isset($name)) $i->setCustomName($name);
			$items[] = $i;
		}
		return $items;
	}

	/**
	 * @return Kit
	 */
	public function getKit() {
		return $this->kit;
	}

	/**
	 * @return PracticePlayer
	 */
	public function getOpponent() {
		return $this->opponent;
	}

	/**
	 * @param PracticePlayer $player
	 * @param ArenaChestInventory $inventory
	 * @param Item $item
	 */
	public function getInteraction(PracticePlayer $player, ArenaChestInventory $inventory, Item $item) {
		if ($item->getId() == Item::STAINED_HARDENED_CLAY) {
			if (!$player->inMatch()) {
				$player->removeMenu();
				$inventory->onClose($player);
				switch ($item->getDamage()) {
					case 13:
						/** @var PracticePlayer[] $players */
						$players = [$player->getName() => $player, $this->getOpponent()->getName() => $this->getOpponent()];
						foreach ($players as $arenaPlayer) {
							if ($arenaPlayer->inQueue()) $arenaPlayer->getQueue()->removePlayer($player);
						}
						if ($player->inMatch()) {
							$player->sendMessage(TextFormat::RED . "You can't join duels while in a match!");
							$this->getOpponent()->sendMessage(TextFormat::RED . "That player is in a match!");
							return;
						} else if ($this->getOpponent()->inMatch()) {
							$this->getOpponent()->sendMessage(TextFormat::RED . "You can't join duels while in a match!");
							$player->sendMessage(TextFormat::RED . "That player is in a match!");
							return;
						}
						$this->getPlugin()->getMatchManager()->createMatch($players, $this->getKit());
						$player->sendMessage(TextFormat::GREEN . "You have accepted " . $this->getOpponent()->getName() . "'s duel request!");
						$this->getOpponent()->sendMessage(TextFormat::GREEN . $player->getName() . " has accepted your duel request!");
						break;
					case 14:
						$player->sendMessage(TextFormat::GREEN . "You have denied " . $this->getOpponent()->getName() . "'s duel request!");
						$this->getOpponent()->sendMessage(TextFormat::RED . $player->getName() . " has denied your duel request!");
						break;
				}
			} else {
				$player->sendMessage(TextFormat::RED . "You are already in a match!");
			}
			$player->setHasDuelRequest(false);
		}
	}

}