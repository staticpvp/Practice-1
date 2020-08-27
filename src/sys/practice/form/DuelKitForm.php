<?php


namespace sys\practice\form;


use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\jordan\core\form\elements\Button;
use sys\jordan\core\form\SimpleForm;

class DuelKitForm extends SimpleForm {

	/**
	 * DuelKitForm constructor.
	 * @param PracticeBase $plugin
	 * @param PracticePlayer $to
	 */
	public function __construct(PracticeBase $plugin, PracticePlayer $to) {
		parent::__construct(TextFormat::RED . "Kit Selector", "");
		foreach($plugin->getKitManager()->getKits() as $kit) {
			$this->addElement(new Button($kit->getName(), function (PracticePlayer $player) use($kit, $plugin, $to): void {
				if ($to->inMatch()) return;
				$to->sendForm(new DuelRequestForm($player, $kit));
				//TODO: Fix crashes w/ Chest UI
//				if($to->isMobile()) {
//
//				} else {
//					$menu = new DuelAcceptMenu($plugin, $player, $kit);
//					$to->addMenu($menu);
//					$to->sendMenu(TextFormat::WHITE . "Duel Request from " . TextFormat::RED . $to->getName() . TextFormat::WHITE . "!");
//				}
				$to->setHasDuelRequest();
				$player->sendMessage(TextFormat::GREEN . "Successfully sent a duel request to {$to->getName()}!");
			}));
		}
	}

}