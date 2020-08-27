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
use sys\practice\Elo;
use sys\practice\kit\Kit;
use sys\jordan\core\base\BaseCommand;

class EditEloCommand extends BaseCommand {

	/**
	 * EditEloCommand constructor.
	 * @param PracticeBase $main
	 */
	public function __construct(PracticeBase $main) {
		parent::__construct($main, "editelo", "Change the elo of anyone", "/editelo [player]");
	}

	/**
	 * @param CommandSender|PracticePlayer $sender
	 * @param array $args
	 * @return bool|string
	 */
	public function onExecute(CommandSender $sender, array $args) {
		if ($sender->isOp()) {
			if (isset($args[0])) {
				if ($args[0] == "resetall") {
					$path = $this->getPlugin()->getServer()->getPluginPath() . DIRECTORY_SEPARATOR . "players";
					$files = scandir($path);
					foreach ($files as $file) {
						if (preg_match('/\.data/', $file)) {
							$cfg = new Config($path . DIRECTORY_SEPARATOR . $file, Config::JSON);
							$elo = [];
							if ($cfg->exists("elo")) {
								foreach ($cfg->get("elo") as $kit => $eloValue) {
									$elo[$kit] = Elo::DEFAULT_ELO;
								}
								$cfg->set("elo", $elo);
								$cfg->save();
							}
						}
					}
					return TextFormat::GREEN . "All elo has been reset!";
				} else {
					$player = $this->getPlugin()->getServer()->getPlayer($args[0]);
					if ($player instanceof PracticePlayer) {
						if (isset($args[1])) {
							switch (strtolower($args[1])) {
								case "reset":
									foreach ($player->getAllElo() as $elo) {
										$elo->setElo(Elo::DEFAULT_ELO);
										$player->saveElo($elo->getKit());
									}
									return TextFormat::GREEN . "Elo reset!";
								case "set":
									if (isset($args[2], $args[3])) {
										/** @var PracticeBase $plugin */
										$plugin = $this->getPlugin();
										$kit = $plugin->getKitManager()->getKitByName($args[2]);
										if ($kit instanceof Kit) {
											$player->getElo($kit)->setElo($args[3]);
											return TextFormat::GREEN . "Elo for kit " . $kit->getName() . " set at $args[3]!";
										}
										return TextFormat::RED . "Kit not found!";
									}
									return TextFormat::RED . $this->getUsage();
							}
						}
					}
				}
			}
			return TextFormat::RED . $this->getUsage();
		}
		return TextFormat::RED . "You must be OP to use this command!";
	}

	/**
	 * @inheritDoc
	 */
	public function setOverloads(): void {
		// TODO: Implement setOverloads() method.
	}
}