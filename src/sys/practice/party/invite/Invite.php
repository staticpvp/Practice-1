<?php
/**
 *
 * This file was created by Matt on 7/11/2017
 * Any attempts to copy, steal, or use this code
 * without permission will result in various consequences.
 *
 */

namespace sys\practice\party\invite;


use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use sys\jordan\core\utils\TickEnum;
use sys\practice\PracticeBase;
use sys\practice\PracticePlayer;

class Invite {

	/** @var int */
	public const INVITE_PERIOD = TickEnum::MINUTE;

	/** @var PracticePlayer */
	private $inviter;

	/** @var PracticePlayer */
	private $invitee;

	/** @var ClosureTask */
	private $task;

	/**
	 * Invite constructor.
	 * @param PracticePlayer $inviter
	 * @param PracticePlayer $invitee
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function __construct(PracticePlayer $inviter, PracticePlayer $invitee) {
		$this->inviter = $inviter;
		$this->invitee = $invitee;
		PracticeBase::getInstance()->getScheduler()->scheduleDelayedTask($this->task = new ClosureTask(function (int $currentTick): void { $this->cancel(); }), self::INVITE_PERIOD);
	}

	/**
	 * @param PracticePlayer $inviter
	 * @param PracticePlayer $invitee
	 * @return Invite
	 */
	public static function create(PracticePlayer $inviter, PracticePlayer $invitee): Invite {
		return new Invite($inviter, $invitee);
	}

	/**
	 * @return PracticePlayer
	 */
	public function getInviter(): PracticePlayer {
		return $this->inviter;
	}

	/**
	 * @return PracticePlayer
	 */
	public function getInvitee(): PracticePlayer {
		return $this->invitee;
	}

	/**
	 * Cancels the current invite
	 */
	public function cancel(): void {
		$plugin = PracticeBase::getInstance();
		if($this->getInviter()->isOnline()) $this->getInviter()->sendMessage(TextFormat::RED . "Your invite to {$this->getInvitee()->getName()} has expired!");
		if($this->getInvitee()->isOnline()) $this->getInvitee()->sendMessage(TextFormat::YELLOW . "The invite from {$this->getInviter()->getName()} has expired!");
		$plugin->getPartyManager()->getInviteHandler()->removeInvite($this);
		$plugin->getScheduler()->cancelTask($this->task->getTaskId());
	}

}