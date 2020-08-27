<?php
/**
 *
 * This file was created by Matt on 7/18/2017
 * Any attempts to copy, steal, or use this code
 * without permission will result in various consequences.
 *
 */

namespace sys\practice\interaction\defaults;


use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\interaction\Interaction;

class QueueInteraction extends Interaction {

	/**
	 * QueueInteraction constructor.
	 * @param PracticeBase $plugin
	 * @param array $items
	 */
	public function __construct(PracticeBase $plugin, array $items = []) {
		parent::__construct($plugin, $items);
	}

	/**
	 * @param PracticePlayer $player
	 * @param Item $item
	 * @return bool
	 */
	public function onInteract(PracticePlayer $player, Item $item): bool {
		switch($item->getId()) {
			case Item::REDSTONE_DUST:
				if($player->inQueue()) {
					$player->getQueue()->removePlayer($player);
					$this->getPlugin()->addLobbyItems($player);
					return true;
				}
				break;
			case Item::PAPER:
				if($player->inQueue()) {
					$player->sendMessage(TextFormat::WHITE . "Kit: " . TextFormat::RED . $player->getQueue()->getKit()->getName());
					$player->sendMessage(TextFormat::WHITE . "Ranked: " . TextFormat::RED . var_export($player->getQueue()->isRanked(), true));
					return true;
				}
				break;
		}
		return false;
	}
}