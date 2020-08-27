<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 7:08 PM
 */

namespace sys\practice\match;

use pocketmine\utils\TextFormat;
use sys\practice\arena\Arena;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\kit\Kit;
use sys\practice\match\event\MatchAddEvent;
use sys\practice\match\event\MatchRemoveEvent;
use sys\practice\task\MatchTask;

class MatchManager {

	/** The maximum amount of matches allowed at a time */
	const MAX_MATCHES = 80;

	/** @var MatchHeartbeat */
	private $heartbeat;

	/** @var PracticeBase */
	private $plugin;

	/** @var Match[] */
	private $matches = [];

	public function __construct(PracticeBase $plugin) {
		$this->plugin = $plugin;
		$this->heartbeat = new MatchHeartbeat($plugin, $this);
	}

	/**
	 * @return Match[]
	 */
	public function getMatches(): array {
		return $this->matches;
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @param int $currentTick
	 */
	public function tickMatches(int $currentTick): void {
		foreach ($this->getMatches() as $match) {
			$match->tick($currentTick);
		}
	}

	/**
	 * @param Match $match
	 */
	public function addMatch(Match $match): void {
		$this->matches[$match->getId()] = $match;
		(new MatchAddEvent($match))->call();
	}

	/**
	 * @param Match $match
	 */
	public function removeMatch(Match $match): void {
		if (isset($this->matches[$match->getId()])) {
			unset($this->matches[$match->getId()]);
			(new MatchRemoveEvent($match))->call();
			unset($match);
		}
	}

	/**
	 * @param PracticePlayer[] $players
	 * @param Kit $kit
	 * @param bool $teams
	 * @return bool
	 *
	 * Returns true if the match is created successfully
	 */
	public function createMatch(array $players, Kit $kit, bool $teams = false): bool {
		$arena = $this->getPlugin()->getArenaManager()->getOpenArena($kit->getMapType());
		if ($arena instanceof Arena) {
			$arena->setInUse();
			new MatchTask($this->getPlugin(), $players, $kit, $arena, $teams);
			foreach($players as $player) $player->sendMessage(TextFormat::GREEN . "Arena found! Creating match...");
			return true;
		} else {
			foreach ($players as $player) $player->sendMessage(TextFormat::RED . "No arenas are available at this time!");
		}
		return false;
	}

}