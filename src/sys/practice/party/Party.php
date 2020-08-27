<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 3/1/2017
 * Time: 4:42 PM
 */

namespace sys\practice\party;


use Exception;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use sys\practice\PracticeBase;
use sys\practice\PracticePlayer;
use function array_filter;
use function array_key_first;
use function array_search;
use function array_shift;

class Party {

	/** @var string */
	public const PREFIX = TextFormat::RED . "(PARTY) " . TextFormat::WHITE . "> ";

	/** @var string */
	private $id;

	/** @var string */
	private $leaderUUID;

	/** @var PracticePlayer[] */
	private $players = [];

	/**
	 * Party constructor.
	 * @param PracticePlayer $leader
	 * @param array $players
	 */
	public function __construct(PracticePlayer $leader, array $players = []) {
		$this->id = md5(base64_encode(mt_rand()));
		$this->leaderUUID = $leader->getUniqueId()->toBinary();
		$this->add($leader);
		foreach($players as $player) $this->add($player);
	}

	/**
	 * @param PracticePlayer $leader
	 * @param array $players
	 * @return Party
	 */
	public static function create(PracticePlayer $leader, array $players = []): Party {
		return new Party($leader, $players);
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return PracticePlayer[]
	 */
	public function getMembers(): array {
		return $this->players;
	}

	/**
	 * @param UUID $uuid
	 * @return PracticePlayer|null
	 */
	public function getMember(UUID $uuid): ?PracticePlayer {
		$filtered = array_filter($this->players, function (PracticePlayer $player) use($uuid): bool { return $player->getUniqueId()->equals($uuid); });
		return count($filtered) > 0 ? $filtered[array_key_first($filtered)] : null;
	}

	/**
	 * @return PracticePlayer[]
	 */
	public function getOnlineMembers(): array {
		return array_filter($this->players, function (PracticePlayer $player): bool {
			return $player->isOnline() && $player->isValid();
		});
	}

	/**
	 * @return PracticePlayer|null
	 */
	public function getLeader(): ?PracticePlayer {
		/** @var PracticePlayer $player */
		$player = PracticeBase::getInstance()->getServer()->getPlayerByRawUUID($this->leaderUUID);
		return $player ?? null;
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function add(PracticePlayer $player): void {

	}

	/**
	 * @param PracticePlayer $player
	 * @param bool $sendMessage
	 * @param bool $forced
	 */
	public function remove(PracticePlayer $player, bool $sendMessage = true, bool $forced = false): void {
		if($this->isPlayer($player)) {
			$player->removeFromParty();
			if(count($this->players) > 1) {
				array_shift($this->players); // shift the leader from the beginning
				if($sendMessage) {
					$this->broadcast(self::PREFIX . $player->getName() . " has " . ($forced ? "been removed from" : "left") . " the party!");
				}
				$this->updateLeader();
			} else {
				$this->delete();
			}
		}
	}

	/**
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function isPlayer(PracticePlayer $player): bool {
		return array_search($player, $this->players, true) !== false;
	}

	/**
	 * @param bool $sendMessage
	 */
	public function updateLeader(bool $sendMessage = true): void {
		/** @var PracticePlayer $leader */
		$leader = array_key_first(array_filter($this->players, function ($player): bool {
			return $player instanceof PracticePlayer && $player->isOnline() && $player->isValid();
		}));
		if($leader !== null) {
			$this->leaderUUID = $leader->getUniqueId()->toBinary();
			if($sendMessage) $this->broadcast("{$leader->getName()} has become the leader of the party!");
		}
	}

	/**
	 * @param string $message
	 */
	public function broadcast(string $message): void {
		foreach ($this->getOnlineMembers() as $member) {
			$member->sendMessage(self::PREFIX . TextFormat::YELLOW . $message);
		}
	}

	/**
	 * @param PracticePlayer $player
	 * @param string $message
	 */
	public function chat(PracticePlayer $player, string $message): void {
		$this->broadcast(TextFormat::WHITE . "{$player->getName()}: $message");
	}

	public function disband(): void {
		if(count($this->players) > 0) {
			foreach($this->players as $index => $player) {
				if($player instanceof PracticePlayer && $player->isOnline()) {
					$this->remove($player, false);
				} else {
					unset($this->players[$index]);
				}
			}
		}
		$this->delete();
	}

	public function delete(): void {
		$plugin = PracticeBase::getInstance();
		try {
			$plugin->getPartyManager()->removeParty($this);
		} catch (Exception $exception) {
			$plugin->getLogger()->error("Unable to delete party {$this->getId()}: {$exception->getMessage()}");
		}
	}

}