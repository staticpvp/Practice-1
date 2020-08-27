<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 9:20 PM
 */

namespace sys\practice\command;

use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\jordan\core\base\BaseUserCommand;

class EloCommand extends BaseUserCommand {

	/** @var Config[] */
	private $cachedPlayers = [];

	public function __construct(PracticeBase $main) {
		parent::__construct($main, "elo", "Get the elo of anyone", "/elo [player] [kit]", []);
	}

	/**
	 * @param CommandSender|PracticePlayer $sender
	 * @param array $args
	 * @return bool|string
	 */
	public function onExecute(CommandSender $sender, array $args){
		$this->sendElo($sender, $args[0] ?? $sender->getName());
		return true;
	}

	/**
	 * @param PracticePlayer $sender
	 * @param string $name
	 * @return void
	 */
	public function sendElo(PracticePlayer $sender, string $name): void {
		$playerObject = $sender->getServer()->getPlayer($name);
		if ($playerObject instanceof PracticePlayer) {
			if($playerObject === $sender) {
				$sender->sendMessage(TextFormat::WHITE . "---- Your Elo ----");
			} else {
				$sender->sendArgsMessage(TextFormat::WHITE . "---- {0}'s Elo ----", $playerObject->getName());
			}
			foreach ($playerObject->getAllElo() as $elo) {
				$sender->sendArgsMessage(TextFormat::WHITE . "{0}: " . TextFormat::RED . "{1}", $elo->getKit()->getName(), $elo->getElo());
			}
		} else {
			$config = $this->findPlayerData($name);
			if($config instanceof Config) {
				$nameTag = $this->getOfflineNameTag($name);
				$elo = $config->get("elo", []);
				if(!empty($elo)) {
					$sender->sendArgsMessage(TextFormat::WHITE . "---- {0}'s Elo ----", $nameTag);
					foreach ($elo as $kit => $eloInt) {
						$sender->sendMessage(TextFormat::WHITE . $kit . ": " . TextFormat::RED . $eloInt);
					}
				}
			} else {
				$sender->sendMessage(TextFormat::RED . "No person under that name was found!");
				return;
			}
		}
		$sender->sendMessage(TextFormat::WHITE . "------------------");
	}

	/**
	 * @param string $name
	 * @return Config|null
	 */
	public function findPlayerData(string $name): ?Config {
		$path = $this->getPlugin()->getDataFolder() . DIRECTORY_SEPARATOR . "players" . DIRECTORY_SEPARATOR . strtolower($name) . ".data";
		if(file_exists($path)) {
			return $this->cachedPlayers[strtolower($name)] ?? ($this->cachedPlayers[strtolower($name)] = new Config($path, Config::JSON));
		}
		return null;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function getOfflineNameTag(string $name): string {
		return $this->getPlugin()->getServer()->getOfflinePlayerData($name)->getString("NameTag", $name);
	}

	/**
	 * @inheritDoc
	 */
	public function setOverloads(): void {
		// TODO: Implement setOverloads() method.
	}
}