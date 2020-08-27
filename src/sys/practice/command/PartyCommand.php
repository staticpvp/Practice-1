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
use sys\practice\form\PartyForm;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\jordan\core\base\BaseUserCommand;
use function count;
use function implode;

class PartyCommand extends BaseUserCommand {

	public function __construct(PracticeBase $main) {
		parent::__construct($main, "party", "Create, invite, and join parties", "/party [chat|invite|kick|accept|deny] (player)");
		$this->setupUsage();
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin() {
		return parent::getPlugin();
	}

	/**
	 * @param CommandSender|PracticePlayer $sender
	 * @param array $args
	 * @return mixed|string
	 */
	public function onExecute(CommandSender $sender, array $args) {
		$count = count($args);
		if($count < 1) {
			(new PartyForm($this->getPlugin(), $sender))->send($sender);
			return true;
		}
	}

	/**
	 * @param CommandSender $sender
	 */
	private function sendUsage(CommandSender $sender): void {
		$sender->sendMessage($this->getUsage());
	}

	private function setupUsage(): void {
		$this->setUsage(implode("\n", [TextFormat::WHITE . "----- Party Command Help -----", TextFormat::RED . "/party accept [player]" . TextFormat::WHITE . " - Accept party invites", TextFormat::RED . "/party chat [msg]" . TextFormat::WHITE . " - Send a chat message to your party", TextFormat::RED . "/party disband" . TextFormat::WHITE . " - Disband a party if in one(Leader only)", TextFormat::RED . "/party deny" . TextFormat::WHITE . " - Deny party invites!", TextFormat::RED . "/party invite [player]" . TextFormat::WHITE . " - Invite players to parties(Creates a party if not in one)", TextFormat::RED . "/party kick [player]" . TextFormat::WHITE . " - Kick members from parties(Leader only)", TextFormat::RED . "/party leave" . TextFormat::WHITE . " - Leave a party if in one", TextFormat::RED . "/party list" . TextFormat::WHITE . " - Lists all members in a party"]));
	}

	/**
	 * @inheritDoc
	 */
	public function setOverloads(): void {}
}