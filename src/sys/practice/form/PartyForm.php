<?php


namespace sys\practice\form;


use pocketmine\utils\TextFormat;
use sys\jordan\core\form\elements\Button;
use sys\jordan\core\form\SimpleForm;
use sys\practice\PracticeBase;
use sys\practice\PracticePlayer;

class PartyForm extends SimpleForm {

	/**
	 * PartyForm constructor.
	 * @param PracticeBase $plugin
	 * @param PracticePlayer $source
	 */
	public function __construct(PracticeBase $plugin, PracticePlayer $source) {
		parent::__construct("Parties", "");
		$this->addElement(new Button(TextFormat::YELLOW . "Send Invite", function (PracticePlayer $player, $data): void {

		}));
		$count = count($plugin->getPartyManager()->getInviteHandler()->getInvites($source));
		$this->addElement(new Button(TextFormat::YELLOW . "Handle Invites [" . TextFormat::WHITE . $count . TextFormat::YELLOW . "]", function (PracticePlayer $player, $data): void {

		}));
	}

}