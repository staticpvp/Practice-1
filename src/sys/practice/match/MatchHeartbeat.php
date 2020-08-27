<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 7:23 PM
 */

namespace sys\practice\match;


use sys\practice\PracticeBase;
use sys\jordan\core\base\BaseTask;

class MatchHeartbeat extends BaseTask {

	/** @var MatchManager */
	private $manager;

	/**
	 * MatchHeartbeat constructor.
	 * @param PracticeBase $plugin
	 * @param MatchManager $manager
	 */
	public function __construct(PracticeBase $plugin, MatchManager $manager) {
		parent::__construct($plugin);
		$this->manager = $manager;
		$this->schedule(1);
	}

	/**
	 * Actions to execute when run
	 *
	 * @param $currentTick
	 *
	 * @return void
	 */
	public function onRun(int $currentTick) {
		if (count($this->getMatchManager()->getMatches()) <= 0) return;
		$this->getMatchManager()->tickMatches($currentTick);
	}

	/**
	 * @return MatchManager
	 */
	public function getMatchManager(): MatchManager {
		return $this->manager;
	}
}