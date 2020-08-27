<?php
/**
 *
 * This file was created by Matt on 7/16/2017
 * Any attempts to copy, steal, or use this code
 * without permission will result in various consequences.
 *
 */

namespace sys\practice\queue;


use pocketmine\utils\TextFormat;
use sys\practice\arena\Arena;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\kit\Kit;
use sys\practice\task\MatchTask;

class Queue {

	/** @var Kit */
	private $kit;

	/** @var int */
	private $os = 0;

	/** @var bool  */
	private $ranked = false;

	/** @var PracticeBase */
	private $plugin;

	/** @var PracticePlayer[] */
	private $players = [];

	/**
	 * Queue constructor.
	 * @param PracticeBase $plugin
	 * @param Kit $kit
	 * @param bool $ranked
	 * @param int $os
	 */
	public function __construct(PracticeBase $plugin, Kit $kit, bool $ranked = false, int $os = 0) {
		$this->plugin = $plugin;
		$this->kit = $kit;
		$this->ranked = $ranked;
		$this->os = $os;
	}

	/**
	 * @return bool
	 */
	public function isRanked(): bool {
		return $this->ranked;
	}

	/**
	 * @return Kit
	 */
	public function getKit(): Kit {
		return $this->kit;
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @return PracticePlayer[]
	 */
	public function getPlayers(): array {
		return $this->players;
	}

	/**
	 * @return int
	 */
	public function getCount(): int {
		return count($this->players);
	}

	/**
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function isPlayer(PracticePlayer $player): bool {
		return isset($this->players[$player->getName()]);
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function addPlayer(PracticePlayer $player): void {
		$this->players[$player->getName()] = $player;
		$player->setQueue($this);
	}

	/**
	 * @param PracticePlayer $player+
	 */
	public function removePlayer(PracticePlayer $player): void {
		if(isset($this->players[$player->getName()])) {
			unset($this->players[$player->getName()]);
			$player->removeFromQueue();
		}
	}

	/**
	 * @param int $count
	 * @return PracticePlayer[]|null
	 */
	public function getRandomPlayers($count = 2): ?array {
		$playerIndexes = array_rand($this->getPlayers(), $count);
		/** @var PracticePlayer[] $players */
		$players = [];
		foreach($playerIndexes as $index) {
			$player = $this->players[$index];
			if ($player->inMatch() and $this->isPlayer($player)) {
				$this->removePlayer($player);
				return $this->getRandomPlayers();
			} else {
				$players[$player->getName()] = $player;
			}
		}

		if(count($players) < $count) {
			$this->getPlugin()->getLogger()->error("Player count is less than expected.");
			return null;
		}

		return $players;
	}

	public function pickMatch(): void {
		if($this->getCount() >= 2) {
			$arena = $this->getPlugin()->getArenaManager()->getOpenArena($this->getKit()->getMapType());
			if ($arena instanceof Arena) {
				$players = $this->getRandomPlayers();
				new MatchTask($this->getPlugin(), $players, $this->getKit(), $arena, false, $this->isRanked());
				foreach ($players as $player) {
					$player->sendArgsMessage(TextFormat::GREEN . "Found a {0} match!", ($this->isRanked() ? "ranked" : "unranked"));
					$this->removePlayer($player);
				}
			}
		}
	}

}