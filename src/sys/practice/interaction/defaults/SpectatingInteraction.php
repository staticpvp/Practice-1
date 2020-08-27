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
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\interaction\Interaction;

class SpectatingInteraction extends Interaction {

	/**
	 * SpectatingInteraction constructor.
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
		if($player->inMatch() && $player->getMatch()->isSpectator($player)) {
			switch($item->getId()) {
				case Item::REDSTONE_TORCH:
					$player->getMatch()->removeSpectator($player);
					$player->removeFromMatch();
					$player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSpawnLocation());
					$this->getPlugin()->addLobbyItems($player);
					return true;
					break;
			}
		}
		return false;
	}
}