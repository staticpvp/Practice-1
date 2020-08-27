<?php


namespace sys\practice\form;


use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\kit\Kit;
use sys\jordan\core\form\ModalForm;

class DuelRequestForm extends ModalForm {

	/**
	 * DuelRequestForm constructor.
	 * @param PracticePlayer $requester
	 * @param Kit $kit
	 */
	public function __construct(PracticePlayer $requester, Kit $kit) {
		parent::__construct(
			TextFormat::RED . "Duel Request from {$requester->getName()}" ,
			TextFormat::WHITE . "You have received a duel request from " . TextFormat::RED . "{$requester->getName()}! \n\n" . TextFormat::WHITE . "Kit: " . TextFormat::RED . $kit->getName()
			, TextFormat::GREEN . "Accept", TextFormat::RED . "Decline",
			function (PracticePlayer $player, &$data) use($requester, $kit): void {
				if($data) {
					$players = [$player, $requester];
					foreach ($players as $arenaPlayer) if ($arenaPlayer->inQueue()) $arenaPlayer->getQueue()->removePlayer($player);
					if ($player->inMatch()) {
						$player->sendMessage(TextFormat::RED . "You can't join duels while in a match!");
						$requester->sendMessage(TextFormat::RED . "That player is in a match!");
						return;
					} else if ($requester->inMatch()) {
						$requester->sendMessage(TextFormat::RED . "You can't join duels while in a match!");
						$player->sendMessage(TextFormat::RED . "That player is in a match!");
						return;
					}
					PracticeBase::getInstance()->getMatchManager()->createMatch($players, $kit);
					$player->sendMessage(TextFormat::GREEN . "You have accepted " . $requester->getName() . "'s duel request!");
					$requester->sendMessage(TextFormat::GREEN . $player->getName() . " has accepted your duel request!");
				} else {
					$player->sendMessage(TextFormat::GREEN . "You have denied " . $requester->getName() . "'s duel request!");
					$requester->sendMessage(TextFormat::RED . $player->getName() . " has denied your duel request!");
				}
				$player->setHasDuelRequest(false);
			});
	}

}