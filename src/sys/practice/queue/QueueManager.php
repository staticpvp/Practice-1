<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/13/2017
 * Time: 5:21 PM
 */

namespace sys\practice\queue;


use pocketmine\network\mcpe\protocol\types\DeviceOS;
use sys\practice\PracticeBase;
use sys\practice\kit\Kit;
use sys\practice\task\QueueTask;

class QueueManager {

	/** @var Queue[] */
	private $unrankedQueue = [];

	/** @var Queue[] */
	private $rankedQueue = [];

	/** @var PracticeBase */
	private $plugin;

	/**
	 * QueueManager constructor.
	 * @param PracticeBase $plugin
	 */
	public function __construct(PracticeBase $plugin) {
		$this->plugin = $plugin;
		$this->loadQueue();
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @return Queue[]
	 */
	public function getRankedQueues(): array {
		return $this->rankedQueue;
	}

	/**
	 * @return Queue[]
	 */
	public function getUnrankedQueues(): array {
		return $this->unrankedQueue;
	}

	/**
	 * @param Kit $kit
	 * @param bool $ranked
	 * @param bool $win10
	 * @return Queue
	 */
	public function getQueue(Kit $kit, bool $ranked = false, $win10 = false): Queue {
		$search = $kit->getName();
		if ($win10) {
			$search .= DeviceOS::WINDOWS_10;
		}
		if ($ranked) {
			return $this->rankedQueue[$search];
		} else {
			return $this->unrankedQueue[$search];
		}
	}

	/**
	 * @param Kit $kit
	 */
	public function createQueue(Kit $kit): void {
		$this->unrankedQueue[$kit->getName()] = new Queue($this->getPlugin(), $kit);
		$this->unrankedQueue[$kit->getName() . DeviceOS::WINDOWS_10] = new Queue($this->getPlugin(), $kit, false, DeviceOS::WINDOWS_10);
		$this->rankedQueue[$kit->getName()] = new Queue($this->getPlugin(), $kit, true);
		$this->rankedQueue[$kit->getName() . DeviceOS::WINDOWS_10] = new Queue($this->getPlugin(), $kit, true, DeviceOS::WINDOWS_10);
	}

	private function loadQueue(): void {
		foreach ($this->getPlugin()->getKitManager()->getKits() as $kit) {
			$this->createQueue($kit);
		}
		new QueueTask($this->getPlugin());
	}

	public function checkQueue(): void {
		foreach ($this->getRankedQueues() as $queue) $queue->pickMatch();
		foreach ($this->getUnrankedQueues() as $queue) $queue->pickMatch();
	}

}