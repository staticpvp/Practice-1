<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 3/1/2017
 * Time: 4:11 PM
 */

namespace sys\practice\party;


use Exception;
use sys\practice\party\invite\InviteHandler;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\utils\PracticeBaseTrait;

class PartyManager {

	use PracticeBaseTrait;

	/** @var Party[] */
	private $parties = [];

	/** @var InviteHandler */
	private $inviteHandler;

	/** @var int */
	public static $MAX_PLAYERS = 25; //TODO: Add option to customize max party size.

	/**
	 * PartyManager constructor.
	 * @param PracticeBase $plugin
	 */
	public function __construct(PracticeBase $plugin) {
		$this->setPlugin($plugin);
		$this->inviteHandler = new InviteHandler();
	}

	/**
	 * @return Party[]
	 */
	public function getParties(): array {
		return $this->parties;
	}

	/**
	 * @return InviteHandler
	 */
	public function getInviteHandler(): InviteHandler {
		return $this->inviteHandler;
	}

	/**
	 * @param Party $party
	 */
	public function addParty(Party $party): void {
		if (!isset($this->parties[$party->getId()])) {
			$this->parties[$party->getId()] = $party;
		}
	}

	/**
	 * @param Party $party
	 * @throws Exception
	 */
	public function removeParty(Party $party): void {
		if(isset($this->parties[$party->getId()])) {
			unset($this->parties[$party->getId()]);
		} else {
			throw new Exception("Party {$party->getId()} is not a valid party in PartyManager");
		}
	}

	/**
	 * @param PracticePlayer $player
	 * @return null|Party
	 */
	public function getPartyFromPlayer(PracticePlayer $player): ?Party {

	}

}