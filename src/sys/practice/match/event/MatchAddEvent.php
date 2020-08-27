<?php


namespace sys\practice\match\event;


use pocketmine\event\Event;
use sys\practice\match\Match;

class MatchAddEvent extends Event {

	/** @var Match */
	private $match;

	public function __construct(Match $match) {
		$this->match = $match;
	}

	/**
	 * @return Match
	 */
	public function getMatch(): Match {
		return $this->match;
	}
}