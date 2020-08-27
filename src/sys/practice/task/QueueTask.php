<?php
/**
 * Created by PhpStorm.
 * User: Matthew
 * Date: 2/23/2017
 * Time: 6:11 PM
 */

namespace sys\practice\task;


use sys\practice\PracticeBase;
use sys\jordan\core\base\BaseTask;

class QueueTask extends BaseTask {

	/**
	 * MatchTask constructor.
	 * @param PracticeBase $plugin
	 */
	public function __construct(PracticeBase $plugin) {
		parent::__construct($plugin);
		$this->schedule(5);
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
		$plugin->getQueueManager()->checkQueue();
	}
}