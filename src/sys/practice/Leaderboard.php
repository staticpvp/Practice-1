<?php


namespace sys\practice;


use sys\practice\kit\Kit;

class Leaderboard {

	/** @var Kit */
	private $kit;

	/** @var PracticeBase */
	private $plugin;

	/** @var int[] */
	private $leaders = [];

	/**
	 * Leaderboard constructor.
	 * @param PracticeBase $plugin
	 * @param Kit $kit
	 */
	public function __construct(PracticeBase $plugin, Kit $kit) {
		$this->plugin = $plugin;
		$this->kit = $kit;
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @return Kit
	 */
	public function getKit(): Kit {
		return $this->kit;
	}

	/**
	 * @return int[]
	 */
	public function getLeaders(): array {
		return $this->leaders;
	}

	public function updateLeaders(): void {
		/** @var PracticePlayer[] $players */
		$players = $this->getPlugin()->getServer()->getOnlinePlayers();
		$mapped = array_map(function (PracticePlayer $player): int {
			return $player->getElo($this->getKit())->getElo();
		}, $players);
		asort($mapped);
		$this->leaders = $mapped;
	}

}