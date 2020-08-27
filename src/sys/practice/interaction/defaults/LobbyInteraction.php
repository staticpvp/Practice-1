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
use sys\practice\form\PartyChoiceForm;
use sys\practice\form\QueueKitForm;
use sys\practice\interaction\Interaction;
use sys\practice\menu\defaults\PartyChoiceMenu;
use sys\practice\menu\defaults\RankedQueueKitMenu;
use sys\practice\menu\defaults\UnrankedQueueKitMenu;

class LobbyInteraction extends Interaction {

	/**
	 * LobbyInteraction constructor.
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
		if(!$player->inMatch()) {
			switch($item->getId()) {
				case Item::EMPTY_MAP:
					if($player->inParty()) {
						$player->sendForm(new PartyChoiceForm($this->getPlugin()));

						//TODO: Fix crashes from Chest UIs
//						if($player->isMobile()) {
//
//						} else {
//							$menu = new PartyChoiceMenu($this->getPlugin());
//							$player->addMenu($menu);
//							$player->sendMenu("Party Events");
//						}
					} else {
						$player->sendMessage(TextFormat::RED."You must be in a party to use this item!");
					}
					return true;
					break;
				case Item::GOLDEN_SWORD:
					if(!$player->inParty()) {
						$player->sendForm(new QueueKitForm($this->getPlugin(), false));
						//TODO: Fix crashes w/ Chest UIs
//						if($player->isMobile()) {
//
//						} else {
//							$menu = new UnrankedQueueKitMenu($this->getPlugin());
//							$player->addMenu($menu);
//							$player->sendMenu("Unranked Queue");
//						}
						return true;
					} else {
						$player->sendMessage(TextFormat::RED."You can't join queues while in a party!");
					}
					break;
				case Item::DIAMOND_SWORD:
					if(!$player->inParty()) {
						$player->sendForm(new QueueKitForm($this->getPlugin(), true));
						//TODO: Fix crashes w/ Chest UIs
//						if($player->isMobile()) {
//						} else {
//							$menu = new RankedQueueKitMenu($this->getPlugin());
//							$player->addMenu($menu);
//							$player->sendMenu("Ranked Queue");
//						}
						return true;
					} else {
						$player->sendMessage(TextFormat::RED."You can't join queues while in a party!");
					}
					break;

			}
		}
		return false;
	}
}