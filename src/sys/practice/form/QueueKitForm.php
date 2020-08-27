<?php


namespace sys\practice\form;


use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\jordan\core\form\elements\Button;
use sys\jordan\core\form\SimpleForm;

class QueueKitForm extends SimpleForm {

	/**
	 * QueueKitForm constructor.
	 * @param PracticeBase $plugin
	 * @param bool $ranked
	 */
	public function __construct(PracticeBase $plugin, bool $ranked) {
		parent::__construct(TextFormat::RED . ($ranked ? "Ranked" : "Unranked") . " Queue Menu", "");
		foreach($plugin->getKitManager()->getKits() as $kit) {
			$this->addElement(new Button($kit->getName(), function (PracticePlayer $player) use($kit, $plugin, $ranked): void {
				if (!$player->isMobile()) {
					$queue = $plugin->getQueueManager()->getQueue($kit, $ranked, true);
				} else {
					$queue = $plugin->getQueueManager()->getQueue($kit, $ranked);
				}
				$queue->addPlayer($player);
				$player->getInventory()->clearAll();
				$player->getInventory()->setItem(0, Item::get(Item::PAPER)->setCustomName(TextFormat::WHITE . "Queue Info"));
				$player->getInventory()->setItem(8, Item::get(Item::REDSTONE)->setCustomName(TextFormat::RED . "Leave Queue"));
				$player->sendMessage(TextFormat::GREEN . "Successfully added to " . $kit->getName() . " queue!");
			}));
		}
	}

}