<?php


namespace sys\practice\party\invite;

use sys\practice\party\PartyManager;
use sys\practice\PracticePlayer;
use function array_filter;

class InviteHandler {

	/** @var PartyManager */
	private $partyManager;

	/** @var Invite[] */
	private $invites = [];

	/**
	 * InviteHandler constructor.
	 * @param PartyManager $partyManager
	 */
	public function __construct(PartyManager $partyManager) {
		$this->partyManager = $partyManager;
	}

	/**
	 * @return PartyManager
	 */
	public function getPartyManager(): PartyManager {
		return $this->partyManager;
	}

	/**
	 * @param PracticePlayer $inviter
	 * @param PracticePlayer $invitee
	 * @return string
	 */
	private function createKey(PracticePlayer $inviter, PracticePlayer $invitee): string {
		return "{$inviter->getUniqueId()->toString()}-{$invitee->getUniqueId()->toString()}";
	}

	/**
	 * @param PracticePlayer $inviter
	 * @param PracticePlayer $invitee
	 * @return bool
	 */
	public function hasInvite(PracticePlayer $inviter, PracticePlayer $invitee): bool {
		return isset($this->invites[$this->createKey($inviter, $invitee)]);
	}

	/**
	 * @param PracticePlayer $inviter
	 * @param PracticePlayer $invitee
	 * @return Invite|null
	 */
	private function getInvite(PracticePlayer $inviter, PracticePlayer $invitee): ?Invite {
		return $this->invites[$this->createKey($inviter, $invitee)] ?? null;
	}

	/**
	 * @param PracticePlayer $invitee
	 * @return array
	 */
	public function getInvitesFor(PracticePlayer $invitee): array {
		return array_filter($this->invites, function (Invite $invite) use($invitee): bool { return $invite->getInvitee() === $invitee; });
	}

	/**
	 * @param PracticePlayer $invitee
	 * @param PracticePlayer $inviter
	 */
	public function addInvite(PracticePlayer $inviter, PracticePlayer $invitee): void {
		if($this->hasInvite($invitee, $invitee)) {
			$this->getPartyManager()->getPlugin()->getLogger()->debug("Player {$inviter->getName()} already has a pending invite to {$invitee->getName()}");
			return;
		}
		$this->invites[$this->createKey($inviter, $invitee)] = Invite::create($inviter, $invitee);
	}

	/**
	 * @param PracticePlayer $inviter
	 * @param PracticePlayer $invitee
	 */
	public function removeInvite(PracticePlayer $inviter, PracticePlayer $invitee): void {
		if(!$this->hasInvite($inviter, $invitee)) {
			$this->getPartyManager()->getPlugin()->getLogger()->debug("Player {$invitee->getName()} doesn't have a pending invite from {$inviter->getName()}");
			return;
		}
		unset($this->invites[$this->createKey($inviter, $invitee)]);
	}

	/**
	 * Clears any pending invites sent by a player
	 *
	 * @param PracticePlayer $inviter
	 */
	public function clearInvitesFrom(PracticePlayer $inviter): void {
		foreach($this->invites as $invite) {
			if($invite->getInviter() === $inviter) {
				$invite->cancel();
			}
		}
	}

	/**
	 * Clears any pending invites to a player
	 *
	 * @param PracticePlayer $invitee
	 */
	public function clearInvitesTo(PracticePlayer $invitee): void {
		foreach($this->invites as $invite) {
			if($invite->getInvitee() === $invitee) {
				$invite->cancel();
			}
		}
	}


}