<?php
/**
 *
 * This file was created by Matt on 7/18/2017
 * Any attempts to copy, steal, or use this code
 * without permission will result in various consequences.
 *
 */

namespace sys\practice;

use sys\practice\kit\Kit;

class Elo {

	/** @var int */
	const DEFAULT_ELO = 1500;

	/** @var Kit */
	private $kit;

	/** @var int */
	private $elo;

	/**
	 * Elo constructor.
	 * @param Kit $kit
	 * @param int $elo
	 */
	public function __construct(Kit $kit, int $elo = self::DEFAULT_ELO) {
		$this->kit = $kit;
		$this->elo = $elo;
	}

	/**
	 * @return Kit
	 */
	public function getKit(): Kit {
		return $this->kit;
	}

	/**
	 * @return int
	 */
	public function getElo(): int {
		return $this->elo;
	}

	/**
	 * @param int $elo
	 */
	public function setElo(int $elo): void {
		$this->elo = $elo;
	}

}