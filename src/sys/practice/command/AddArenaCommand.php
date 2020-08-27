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
use sys\jordan\core\base\BaseUserCommand;

class AddArenaCommand extends BaseUserCommand {

	/**
	 * AddArenaCommand constructor.
	 * @param PracticeBase $main
	 */
	public function __construct(PracticeBase $main) {
		parent::__construct($main, "addarena", "Add arenas", "/addarena [option]", ["aa", "arena"]);
	}

	/**
	 * @param CommandSender|PracticePlayer $sender
	 * @param array $args
	 * @return mixed|string
	 */
	public function onExecute(CommandSender $sender, array $args) {
		if ($sender->isOp()) {
			/** @var PracticeBase $plugin */
			$plugin = $this->getPlugin();
			if (isset($args[0])) {
				switch (strtolower($args[0])) {
					case "help":
						$this->sendArenaHelp($sender);
						return true;
					case "save":
						if (isset($args[1])) {
							$arena = $plugin->getArenaManager()->getArenaById($args[1] - 1);
							if (!$arena) {
								return TextFormat::RED . "No arena was found by that name!";
							}
							$sender->sendMessage(TextFormat::GREEN . "Saving arena...");
							$start = microtime(true);
							$arena->saveChunks();
							return TextFormat::GREEN . "Time taken: " . (number_format(microtime(true) - $start, 3)) . "s";
						}
						break;
					case "count":
						return TextFormat::GREEN . count($plugin->getArenaManager()->getArenas()) . " arenas loaded! (" . count($plugin->getArenaManager()->getOpenArenas()) . "open)";
					case "reset":
						if (isset($args[1])) {
							$arena = $plugin->getArenaManager()->getArenaById($args[1] - 1);
							if ($arena === null) {
								return TextFormat::RED . "No arena was found by that name!";
							}
							$sender->sendMessage(TextFormat::GREEN . "Resetting arena...");
							$start = microtime(true);
							$arena->resetArena();
							return TextFormat::GREEN . "Time taken: " . (number_format(microtime(true) - $start, 3)) . "s";
						}
						break;
					case "remove":
						if (isset($args[1])) {
							$name = $plugin->getArenaManager()->getArenaById($args[1] - 1);
							if (!$name) {
								return TextFormat::RED . "No arena was found by that name!";
							}
							$sender->sendMessage(TextFormat::GREEN . "Removing arena...");
							return $plugin->getArenaManager()->deleteArena($args[1] - 1);
						}
						break;
					case "teleport":
					case "tp":
						if ($sender->inMatch()) return TextFormat::RED . "You can't do this while in a match!";
						$arena = $plugin->getArenaManager()->getArenaById($args[1] - 1);
						if (!$arena) {
							return TextFormat::RED . "No arena was found by that name!";
						}
						$sender->teleport($arena->getRandomPosition());
						return TextFormat::GREEN . "Teleporting to arena #" . ($arena->getId() + 1) . "...";
						break;
					case "create":
						if(!$plugin->getArenaManager()->isEditing($sender)) {
							$plugin->getArenaManager()->setEditing($sender);
							$plugin->getArenaManager()->getEditor($sender)->handleMessages();
							return true;
						}
						return TextFormat::RED . "You are already creating an arena! Use '/arena cancel' to cancel!";
					case "cancel":
						if($plugin->getArenaManager()->isEditing($sender)) {
							$plugin->getArenaManager()->cancelEditing($sender);
							return TextFormat::GREEN . "Arena creation cancelled!";
						}
						return TextFormat::RED . "You are not creating an arena! Use '/arena create' to create an arena!";
				}
			}
			$this->sendArenaHelp($sender);
			return true;
		}
		return TextFormat::RED . "You must be OP to use this command!";
	}

	/**
	 * @param CommandSender $sender
	 */
	private function sendArenaHelp(CommandSender $sender) {
		$messages = [
			TextFormat::WHITE . "----- Arena Command Help -----",
			TextFormat::RED . "/addarena cancel" . TextFormat::WHITE . "- Cancels the creation of an arena",
			TextFormat::RED . "/addarena create" . TextFormat::WHITE . "- Starts the creation of an arena",
			TextFormat::RED . "/addarena count" . TextFormat::WHITE . " - Tells the sender how many arenas there are",
			TextFormat::RED . "/addarena remove [arena-id]" . TextFormat::WHITE . " - Removes an existing arena from the list by it's id",
			TextFormat::RED . "/addarena reset [arena-id]" . TextFormat::WHITE . " - Resets an existing arena by it's id",
			TextFormat::RED . "/addarena save [arena-id]" . TextFormat::WHITE . " - Saves an existing arena by it's id",
			TextFormat::RED . "/addarena tp [arena-id]" . TextFormat::WHITE . " - Teleports the sender to an arena by it's id",
			TextFormat::WHITE . "------------------------------"];
		foreach ($messages as $message) {
			$sender->sendMessage($message);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setOverloads(): void {
		// TODO: Implement setOverloads() method.
	}
}
