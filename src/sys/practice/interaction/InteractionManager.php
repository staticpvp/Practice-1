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
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\interaction\defaults\LobbyInteraction;
use sys\practice\interaction\defaults\QueueInteraction;
use sys\practice\interaction\defaults\SpectatingInteraction;

class InteractionManager {

	/** @var Interaction[] */
	private $interactions = [];

	/** @var PracticeBase */
	private $plugin;

	public function __construct(PracticeBase $plugin) {
		$this->plugin = $plugin;
		$this->initInteractions();
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @return Interaction[]
	 */
	public function getInteractions(): array {
		return $this->interactions;
	}

	public function initInteractions(): void {
		$this->addInteraction(new QueueInteraction($this->getPlugin(), [
			Item::get(Item::REDSTONE_DUST)->setCustomName("Leave Queue"),
			Item::get(Item::PAPER)->setCustomName("Queue Info")
		]));

		$this->addInteraction(new LobbyInteraction($this->getPlugin(), [
			Item::get(Item::EMPTY_MAP)->setCustomName("Start Party Event"),
			Item::get(Item::GOLDEN_SWORD)->setCustomName("Join Unranked Queue"),
			Item::get(Item::DIAMOND_SWORD)->setCustomName("Join Ranked Queue")
		]));

		$this->addInteraction(new SpectatingInteraction($this->getPlugin(), [
			Item::get(Item::REDSTONE_TORCH)->setCustomName("Spectator Toggle Off"),
		]));

	}

	/**
	 * @param Item $item
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function matchesInteraction(Item $item, PracticePlayer $player): bool {
		foreach ($this->getPlugin()->getInteractionManager()->getInteractions() as $interaction) {
			if ($interaction->exists($item)) {
				$interaction->onInteract($player, $item);
				return true;
			}
		}
		return false;
	}

	/**
	 * @param Interaction $interaction
	 */
	public function addInteraction(Interaction $interaction): void {
		$this->interactions[spl_object_hash($interaction)] = $interaction;
	}

	/**
	 * @param Interaction $interaction
	 */
	public function removeInteraction(Interaction $interaction): void {
		if (isset($this->interactions[spl_object_hash($interaction)])) unset($this->interactions[spl_object_hash($interaction)]);
	}



}