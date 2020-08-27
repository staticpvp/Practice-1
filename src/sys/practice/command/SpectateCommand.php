<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 9:20 PM
 */

namespace sys\practice\command;

use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\jordan\core\base\BaseUserCommand;

class SpectateCommand extends BaseUserCommand {

	public function __construct(PracticeBase $main) {
		parent::__construct($main, "spectate", "Spectate other players", "/spectate [player]", ["spec"]);
	}

	/**
	 * @param CommandSender|PracticePlayer $sender
	 * @param array $args
	 * @return bool|mixed|string
	 */
	public function onExecute(CommandSender $sender, array $args) {
		if (count($args) > 0) {
			$player = $sender->getServer()->getPlayer($args[0]);
			if ($sender === $player) return TextFormat::RED . "You can't spectate yourself!";

			//If the player is not online, it'll return null, and null equates to false, and the opposite of false is true :^)
			if (!$player or !$player instanceof PracticePlayer) return TextFormat::RED . "That player is not online!";

			if ($sender->inMatch()) return TextFormat::RED . "You can't spectate whilst in a match!";

			if (!$player->inMatch()) return TextFormat::RED . "That player is not in a match!";

			$sender->teleport($player);
			$player->getMatch()->join($sender);
			$sender->getInventory()->setItem(8, Item::get(Item::REDSTONE_TORCH)->setCustomName(TextFormat::GREEN . "Spectator Toggle Off"));

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