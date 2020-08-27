<?php


namespace sys\practice\utils;


use sys\practice\PracticeBase;

trait PracticeBaseTrait {

	/** @var PracticeBase */
	protected $plugin;

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @param PracticeBase $plugin
	 */
	public function setPlugin(PracticeBase $plugin): void {
		$this->plugin = $plugin;
	}

}