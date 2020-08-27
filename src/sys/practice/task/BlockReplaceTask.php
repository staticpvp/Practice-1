<?php
/**
 *
 * This file was created by Matt on 8/7/2017
 * Any attempts to copy, steal, or use this code
 * without permission will result in various consequences.
 *
 */

namespace sys\practice\task;


use pocketmine\block\Block;
use pocketmine\Player;
use sys\practice\PracticeBase;
use sys\jordan\core\base\BaseTask;

class BlockReplaceTask extends BaseTask {

	/** @var Block $block */
	private $block;

	/** @var Player $player */
	private $player;


	/**
	 * @param Block $block
	 * @param Player $player
	 */
	public function __construct(Block $block, Player $player) {
		parent::__construct(PracticeBase::getInstance());
		$this->block = $block;
		$this->player = $player;
		$this->schedule(-1, 5);
	}

	/**
	 * Actions to execute when run
	 *
	 * @param int $currentTick
	 *
	 * @return void
	 */
	public function onRun(int $currentTick) {
		$this->block->getLevel()->sendBlocks([$this->player], [$this->block]);
		unset($this->player, $this->block);
	}
}