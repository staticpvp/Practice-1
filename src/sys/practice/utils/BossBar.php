<?php
/**
 *
 * This file was created by Matt on 9/30/2017
 * Any attempts to copy, steal, or use this code
 * without permission will result in various consequences.
 *
 */

namespace sys\practice\utils;

use pocketmine\entity\Squid;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\Player;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;


/*
 * TODO: Rewrite this a bit
 */

class BossBar {

	/** @var PracticeBase */
	private $plugin;

	/** @var string */
	private $title = "";

	/** @var Player[] */
	private $players = [];

	const ENTITY_ID = 2500;

	public static $Y_SUBTRACTION = 10; //I personally wouldn't go too far away :P

	/**
	 * BossBar constructor.
	 * @param PracticeBase $plugin
	 */
	public function __construct(PracticeBase $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @param Player $player
	 * @return bool
	 */
	public function hasPlayer(Player $player): bool {
		return isset($this->players[$player->getName()]);
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @return PracticePlayer[]
	 */
	public function getPlayers(): array {
		return $this->players;
	}

	public function addBossBar(PracticePlayer $player) {
		$this->addEntity($player);
		$this->players[$player->getName()] = $player;
		$pk = new BossEventPacket();
		$pk->unknownShort = 1;
		$pk->overlay = 1;
		$pk->color = 1;
		$pk->bossEid = self::ENTITY_ID;
		$pk->eventType = BossEventPacket::TYPE_SHOW;
		$pk->title = "";
		$pk->healthPercent = 0;
		$player->dataPacket($pk);
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function removeBossBar(PracticePlayer $player): void {
		unset($this->players[$player->getName()]);
		$this->removeEntity($player);
	}

	/**
	 * @param float $value
	 */
	public function setBossBarProgress(float $value) {
		$pk = new BossEventPacket();
		$pk->bossEid = self::ENTITY_ID;
		$pk->eventType = BossEventPacket::TYPE_HEALTH_PERCENT;
		foreach ($this->getPlayers() as $player) {
			$pk->healthPercent = $value;
			$player->dataPacket($pk);
		}
	}

	/**
	 * @param string $title
	 */
	public function setBossBarTitle(string $title): void {
		$this->title = $title;
		$pk = new BossEventPacket();
		$pk->bossEid = self::ENTITY_ID;
		$pk->eventType = BossEventPacket::TYPE_TITLE;
		foreach ($this->getPlayers() as $player) {
			$pk->title = "\n\n" . $title;
			$player->dataPacket($pk);
		}
	}

	/**
	 * @param string $title
	 * @param string ...$args
	 */
	public function setBossBarTitleWithArgs(string $title, ...$args) {
		for ($i = 0; $i < count($args); $i++) {
			$title = str_replace("{" . $i . "}", $args[$i], $title);
		}
		$this->setBossBarTitle($title);
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function addEntity(PracticePlayer $player): void {
		$pk = new AddActorPacket();
		$pk->entityRuntimeId = self::ENTITY_ID;
		$pk->type = Squid::NETWORK_ID;
		$pk->position = $player->subtract(0, self::$Y_SUBTRACTION);
		$pk->motion = new Vector3(0, 0, 0);
		$pk->yaw = 0.0;
		$pk->pitch = 0.0;
		$flags = 0;
		$flags |= 1 << Squid::DATA_FLAG_SILENT;
		$flags |= 1 << Squid::DATA_FLAG_IMMOBILE;
		$flags |= 1 << Squid::DATA_FLAG_INVISIBLE;
		$pk->metadata = [Squid::DATA_FLAGS => [Squid::DATA_TYPE_LONG, $flags], Squid::DATA_AIR => [Squid::DATA_TYPE_SHORT, 400], Squid::DATA_MAX_AIR => [Squid::DATA_TYPE_SHORT, 400], Squid::DATA_NAMETAG => [Squid::DATA_TYPE_STRING, "\n\n" . ""], //The two line breaks are so the text don't overlap with the boss bar.
			Squid::DATA_LEAD_HOLDER_EID => [Squid::DATA_TYPE_LONG, -1], Squid::DATA_SCALE => [Squid::DATA_TYPE_FLOAT, 0.01], Squid::DATA_BOUNDING_BOX_WIDTH => [Squid::DATA_TYPE_FLOAT, 0], Squid::DATA_BOUNDING_BOX_HEIGHT => [Squid::DATA_TYPE_FLOAT, 0]];
		$player->dataPacket($pk);
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function moveEntity(PracticePlayer $player): void {
		$pk = new MoveActorAbsolutePacket();
		$pk->entityRuntimeId = self::ENTITY_ID;
		$pk->position = $player->subtract(0, self::$Y_SUBTRACTION);
		$pk->xRot = 0;
		$pk->yRot = 0;
		$pk->zRot = 0;
		$player->dataPacket($pk);
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function removeEntity(PracticePlayer $player): void {
		$pk = new RemoveActorPacket();
		$pk->entityUniqueId = self::ENTITY_ID;
		$player->dataPacket($pk);
	}


}