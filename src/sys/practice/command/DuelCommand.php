<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 9:20 PM
 */

namespace sys\practice\command;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\form\DuelKitForm;
use sys\practice\menu\defaults\DuelKitMenu;
use sys\jordan\core\base\BaseUserCommand;

class DuelCommand extends BaseUserCommand {

	public function __construct(PracticeBase $main) {
		parent::__construct($main, "duel", "Duel other players", "/duel [player]");
	}

	/**
	 * @param CommandSender|PracticePlayer $sender
	 * @param array $args
	 * @return bool|mixed|string
	 */
	public function onExecute(CommandSender $sender, array $args) {
		if (count($args) > 0) {
			/** @var PracticeBase $plugin */
			$plugin = $this->getPlugin();
			$player = $sender->getServer()->getPlayer($args[0]);
			if ($sender === $player) return TextFormat::RED . "You can't duel yourself!";

			if (!$player or (!$player instanceof PracticePlayer) or !$player->isOnline()) return TextFormat::RED . "That player is not online!";

			if (!$player->hasDuelRequestsEnabled()) return TextFormat::RED . "This player is not accepting duel requests at this time!";

			if ($sender->inParty()) return TextFormat::RED . "You can't duel players while in a party!";

			if ($player->inParty()) return TextFormat::RED . "You can't duel players while they are in a party!";

			if ($player->inMatch()) return TextFormat::RED . "You can't duel players if they are already in a match!";

			if ($sender->inMatch()) return TextFormat::RED . "You can't duel while in a match!";

			$sender->sendForm(new DuelKitForm($this->getPlugin(), $player));
			//TODO: Fix crashes w/ Chest UIs
//			if($sender->isMobile()) {
//
//			} else {
//				$menu = new DuelKitMenu($plugin, $player);
//				$sender->addMenu($menu);
//				$sender->sendMenu(TextFormat::WHITE . "Kit Selector");
//			}
			return true;
		}
		return TextFormat::RED . "Usage: " . $this->getUsage();
	}

	/**
	 * @inheritDoc
	 */
	public function setOverloads(): void {
		// TODO: Implement setOverloads() method.
	}
}