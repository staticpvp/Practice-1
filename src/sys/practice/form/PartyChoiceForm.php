<?php


namespace sys\practice\form;


use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\jordan\core\form\elements\Button;
use sys\jordan\core\form\SimpleForm;

class PartyChoiceForm extends SimpleForm {

	/**
	 * PartyChoiceForm constructor.
	 * @param PracticeBase $plugin
	 */
	public function __construct(PracticeBase $plugin) {
		parent::__construct("Party Events", "");
		$this->addElement(new Button("FFA"));
		$this->addElement(new Button("Teams"));
		$this->setCallable(function (PracticePlayer $player, $data) use($plugin) {
			if($data !== null) {
				$teams = $data > 0;
				if($teams) {
					$player->sendMessage(TextFormat::RED . "Team matches are disabled right now!");
					return;
				}
				$player->sendForm(new class($plugin, $teams) extends SimpleForm {
					public function __construct(PracticeBase $plugin, bool $teams) {
						parent::__construct("Kit Selector", "");
						foreach($plugin->getKitManager()->getKits() as $kit) {
							$this->addElement(new Button($kit->getName(), function (PracticePlayer $player) use($kit, $plugin, $teams): void {
								$message = $teams ? "Trying to start a team event..." : "Trying to start an FFA event...";
								$player->getParty()->broadcast($message);
								$plugin->getMatchManager()->createMatch($player->getParty()->getOnlineMembers(), $kit, $teams);
							}));
						}
					}
				});
			}
		});
	}

}