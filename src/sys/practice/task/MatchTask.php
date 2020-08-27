<?php
/**
 * Created by PhpStorm.
 * User: Matthew
 * Date: 2/23/2017
 * Time: 6:11 PM
 */

namespace sys\practice\task;


use sys\practice\arena\Arena;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\kit\Kit;
use sys\practice\match\Match;
use sys\practice\match\RankedMatch;
use sys\practice\match\TeamMatch;
use sys\jordan\core\base\BaseTask;

class MatchTask extends BaseTask {

	/** @var Match|null */
	private $match = null;

	/**
	 * MatchTask constructor.
	 * @param PracticeBase $plugin
	 * @param PracticePlayer[] $players
	 * @param Kit $kit
	 * @param Arena $arena
	 * @param bool $teams
	 * @param bool $ranked
	 */
	public function __construct(PracticeBase $plugin, array $players, Kit $kit, Arena $arena, bool $teams = false, bool $ranked = false) {
		parent::__construct($plugin);
		$this->schedule(-1, 20 * 3);
		$this->createMatch($plugin, $players, $kit, $arena, $teams, $ranked);
	}

	/**
	 * @param PracticeBase $plugin
	 * @param array $players
	 * @param Kit $kit
	 * @param Arena $arena
	 * @param bool $teams
	 * @param bool $ranked
	 */
	public function createMatch(PracticeBase $plugin, array $players, Kit $kit, Arena $arena, bool $teams = true, bool $ranked = true) {
		if($ranked) {
			if($teams) {
				$this->match = new RankedMatch($plugin, $kit, $players, $arena, $ranked);
			} else {
				$this->match = new RankedMatch($plugin, $kit, $players, $arena, $ranked);
			}
		} else {
			if ($teams) {
				$this->match = new TeamMatch($plugin, $kit, $players, $arena, $ranked);
			} else {
				$this->match = new Match($plugin, $kit, $players, $arena, $ranked);
			}
		}
	}

	/**
	 * Actions to execute when run
	 *
	 * @param $currentTick
	 *
	 * @return void
	 */
	public function onRun(int $currentTick): void {
		/** @var PracticeBase $plugin */
		$plugin = $this->getPlugin();
		$plugin->getMatchManager()->addMatch($this->match);
		$this->match->init();
	}
}