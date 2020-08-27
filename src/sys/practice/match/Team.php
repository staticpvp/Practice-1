<?php
/**
 *
 * This file was created by Matt on 7/28/2017
 * Any attempts to copy, steal, or use this code
 * without permission will result in various consequences.
 *
 */

namespace sys\practice\match;


use sys\practice\PracticePlayer;

class Team {

	/** @var int */
	private $playerCount = 0;

	/** @var PracticePlayer[] */
	private $players = [];

	/**
	 * Team constructor.
	 * @param PracticePlayer[] ...$players
	 */
	public function __construct(...$players) {
		$this->players = $players;
	}

	/**
	 * @return PracticePlayer[]
	 */
	public function getPlayers(): array {
		return $this->players;
	}

	public function hasPlayer(PracticePlayer $player) {
		return isset($this->players[$player->getName()]);
	}

	public function onTeam(PracticePlayer $firstPlayer, PracticePlayer $secondPlayer) {
		return isset($this->players[$firstPlayer->getName()], $this->players[$secondPlayer->getName()]);
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function addPlayer(PracticePlayer $player): void {
		if(!isset($this->players[$player->getName()])) {
			$this->players[$player->getName()] = $player;
			$this->playerCount++;
		}
	}

	public function subtractPlayerCount(): void {
		$this->playerCount--;
	}

	/**
	 * @return int
	 */
	public function getPlayerCount(): int {
		return $this->playerCount;
	}

	/**
	 * @param string $message
	 */
	public function sendMessage(string $message): void {
		foreach($this->getPlayers() as $player) $player->sendMessage($message);
	}

	/**
	 * @param string $message
	 * @param string[] ...$args
	 * It'd be easier to do a foreach loop and send an args message per person, but
	 * it'd register $args as one parameter only
	 */
	public function sendArgsMessage(string $message, ... $args) {
		for($i = 0; $i < count($args); $i++) {
			$message = str_replace("{" . $i . "}", $args[$i], $message);
		}
		$this->sendMessage($message);
	}

	public function nullify(): void {
		$this->playerCount = null;
		$this->players = null;
	}

	/**
	 * @return bool
	 */
	public function isDead(): bool {
		return $this->getPlayerCount() <= 0;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		$string = "[";
		$string .= implode(", ", array_map(function(PracticePlayer $player) {
			return $player->getDisplayName();
		}, $this->getPlayers()));
		$string = rtrim($string, ",");
		$string .= "]";
		return $string;
	}

}