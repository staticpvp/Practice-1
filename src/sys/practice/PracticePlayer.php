<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 6:17 PM
 */

namespace sys\practice;


use pocketmine\block\Block;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use sys\practice\kit\Kit;
use sys\practice\match\Match;
use sys\practice\menu\Menu;
use sys\practice\party\Party;
use sys\practice\queue\Queue;
use sys\practice\utils\ArenaChest;
use sys\jordan\core\CorePlayer;
use sys\jordan\core\utils\Scoreboard;


/*
 * TODO: Rewrite some of the hacks I implemented in a couple of months back.
 */
class PracticePlayer extends CorePlayer {

	/** @var int */
	const BASE_KFACTOR = 32;

	/** @var bool */
	private $duelRequestsEnabled = true;

	/** @var bool */
	private $partyInvitesEnabled = true;

	/** @var Party|null */
	private $party = null;

	/** @var Queue|null */
	private $queue = null;

	/** @var int */
	private $searchDifference = 100;

	/** @var int */
	private $rankedMatchesLeft = 15;

	/** @var int */
	private $unrankedMatchesLeft = 40;

	/** @var bool */
	private $duelRequest = false;

	/** @var Match */
	private $match = null;

	/** @var Menu */
	private $menu = null;

	/** @var Config */
	private $data = null;

	/** @var Elo[] */
	private $elo = [];

	/**
	 * @param string $message
	 * @param string ...$args
	 */
	public function sendArgsMessage(string $message, ...$args) {
		for ($i = 0; $i < count($args); $i++) {
			$message = str_replace("{" . $i . "}", $args[$i], $message);
		}
		$this->sendMessage($message);
	}

	/**
	 * @return Config
	 */
	public function getData(): Config {
		return $this->data;
	}

	public function loadData(): void {
		$this->data = new Config(PracticeBase::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . PracticeBase::FOLDER_NAME . DIRECTORY_SEPARATOR . $this->getLowerCaseName() . ".data", Config::JSON);
	}

	/**
	 * @return bool
	 */
	public function inMatch(): bool {
		return $this->match instanceof Match;
	}

	/**
	 * @return Match|null
	 */
	public function getMatch(): ?Match {
		return $this->match;
	}
	/**
	 * @param Match $match
	 */
	public function setMatch(Match $match): void {
		$this->match = $match;
	}

	public function removeFromMatch(): void {
		$this->match = null;
	}


	/**
	 * @return bool
	 */
	public function inQueue(): bool {
		return $this->queue instanceof Queue;
	}

	/**
	 * @return Queue|null
	 */
	public function getQueue(): ?Queue {
		return $this->queue;
	}

	/**
	 * @param Queue|null $queue
	 */
	public function setQueue(?Queue $queue): void {
		$this->queue = $queue;
	}


	public function removeFromQueue(): void {
		if($this->inQueue()) {
			$this->getQueue()->removePlayer($this);
			$this->queue = null;
		}
	}

	/**
	 * @param Menu $menu
	 */
	public function addMenu(Menu $menu): void {
		$this->menu = $menu;
	}

	/**
	 * @param string $name
	 */
	public function sendMenu(string $name): void {
		if ($this->inMenu()) {
			$tag = new CompoundTag();
			$tag->setString("CustomName", $name);
			$tag->setString("id", Tile::CHEST);
			$tag->setInt("x", $this->getFloorX());
			$tag->setInt("y", $this->getFloorY() + 4);
			$tag->setInt("z", $this->getFloorZ());
			$tile = Tile::createTile("ArenaChest", $this->getLevel(), $tag);
			$block = (Block::get(Block::CHEST))->setComponents($tile->getX(), $tile->getY(), $tile->getZ());
			$block->setLevel($tile->getLevel());
			$block->getLevel()->sendBlocks([$this], [$block]);
			if ($tile instanceof ArenaChest) {
				$tile->getInventory()->setContents($this->getMenu()->getItems());
				$this->addWindow($tile->getInventory());
			} else {
				$this->removeMenu();
			}
		}
	}

	/**
	 * @return bool
	 */
	public function inMenu(): bool {
		return $this->menu instanceof Menu;
	}

	/**
	 * @return Menu|null
	 */
	public function getMenu(): ?Menu {
		return $this->menu ?? null;
	}

	public function removeMenu(): void {
		$this->menu = null;
	}

	/**
	 * @return bool
	 */
	public function hasDuelRequestsEnabled(): bool {
		return $this->duelRequestsEnabled;
	}

	/**
	 * @param bool $value
	 */
	public function setDuelRequestsEnabled($value = false): void {
		$this->duelRequestsEnabled = $value;
	}

	/**
	 * @return bool
	 */
	public function hasPartyInvitesEnabled(): bool {
		return $this->partyInvitesEnabled;
	}

	/**
	 * @param bool $value
	 */
	public function setPartyInvitesEnabled($value = false): void {
		$this->partyInvitesEnabled = $value;
	}

	/**
	 * @return bool
	 */
	public function hasDuelRequest(): bool {
		return $this->duelRequest;
	}

	/**
	 * @param bool $value
	 */
	public function setHasDuelRequest($value = true): void {
		$this->duelRequest = $value;
	}

	/**
	 * @param Party|null $party
	 */
	public function setParty(?Party $party): void {
		$this->party = $party;
	}

	public function removeFromParty(): void {
		$this->setParty(null);
	}

	/**
	 * @return null|Party
	 */
	public function getParty(): ?Party {
		return $this->party;
	}

	/**
	 * @return bool
	 */
	public function inParty(): bool {
		return $this->party instanceof Party;
	}

	/**
	 * @param string $name
	 * @param PracticePlayer[] $players
	 */
	public function setCustomNameTag(string $name, array $players): void {
		foreach ($players as $player) {
			$this->sendData($player, [self::DATA_NAMETAG => [self::DATA_TYPE_STRING, $name]]);
		}
	}

	public function removeAllEffects(): void {
		foreach ($this->getEffects() as $effect) {
			$this->removeEffect($effect->getId());
		}
	}

	/**
	 * @param bool $addEffects
	 */
	public function setSpectating($addEffects = false): void {
		if ($addEffects) {
			$this->addEffect((new EffectInstance(Effect::getEffect(Effect::BLINDNESS)))->setDuration(20 * 2)->setAmplifier(3));
			$this->addEffect((new EffectInstance(Effect::getEffect(Effect::SLOWNESS)))->setDuration(20 * 2)->setAmplifier(3));
		}
		$this->reset(self::SPECTATOR);
		if($this->inMatch()) {
			$match = $this->getMatch();
			if(!$match->getBossBar()->hasPlayer($this)) {
				$match->getBossBar()->addBossBar($this);
			}
		}
	}

	/**
	 * @param Kit $kit
	 * @return int
	 *
	 * Returns the player's KFactor for a specific kit.
	 *
	 * TODO: Make these values configurable.
	 */
	public function getKFactor(Kit $kit): int {
		$elo = $this->getElo($kit)->getElo();
		if ($elo < 1600) {
			$kFactor = self::BASE_KFACTOR;
		} else if ($elo >= 1600 and $elo < 2000) {
			$kFactor = round(self::BASE_KFACTOR / 1.333);
		} else if ($elo >= 2000 and $elo <= 2400) {
			$kFactor = round(self::BASE_KFACTOR / 2);
		} else {
			$kFactor = round(self::BASE_KFACTOR / 4);
		}
		return $kFactor;
	}

	public function loadElo(): void {
		if(!$this->getData()->exists("elo")) {
			$this->getData()->set("elo", []);
		}
		$elo = $this->getData()->get("elo");
		foreach (PracticeBase::getInstance()->getKitManager()->getKits() as $kit) {
			if (isset($elo[$kit->getName()])) {
				$this->addElo($kit, (int)$elo[$kit->getName()]);
			} else {
				$this->addElo($kit);
				$elo[$kit->getName()] = $this->getElo($kit)->getElo();
			}
		}
		$this->getData()->set("elo", $elo);
		$this->getData()->save();
	}

	/**
	 * @return Elo[]
	 */
	public function getAllElo(): array {
		return $this->elo;
	}

	/**
	 * @param Kit $kit
	 * @param int $elo
	 */
	public function boostElo(Kit $kit, int $elo): void {
		$eloKit = $this->elo[$kit->getName()];
		$eloKit->setElo($eloKit->getElo() + $elo);
	}

	/**
	 * @param Kit $kit
	 * @param int $elo
	 */
	public function subtractElo(Kit $kit, int $elo): void {
		$eloKit = $this->elo[$kit->getName()];
		$eloKit->setElo($eloKit->getElo() - $elo);
	}

	/**
	 * @param Kit $kit
	 * @param int $elo
	 */
	public function addElo(Kit $kit, int $elo = Elo::DEFAULT_ELO): void {
		$this->elo[$kit->getName()] = new Elo($kit, $elo);
	}

	/**
	 * @param Kit $kit
	 */
	public function saveElo(Kit $kit): void {
		$eloArray = $this->getData()->get("elo");
		$eloArray[$kit->getName()] = $this->getElo($kit)->getElo();
		$this->getData()->set("elo", $eloArray);
		$this->getData()->save();
	}

	/**
	 * @param Kit $kit
	 * @return Elo
	 */
	public function getElo(Kit $kit): Elo {
		return $this->elo[$kit->getName()];
	}

	public function dropAllItems(): void {
		$items = array_merge($this->getInventory()->getContents(), $this->getArmorInventory()->getContents(),$this->getCursorInventory()->getContents());
		foreach ($items as $item) {
			$this->getLevel()->dropItem($this, $item);
		}
	}

	/**
	 * @param int $gamemode
	 */
	public function reset(int $gamemode = self::ADVENTURE): void {
		$this->setGamemode($gamemode);
		$this->getInventory()->clearAll();
		$this->getArmorInventory()->clearAll();
		$this->getCursorInventory()->clearAll();
		$this->removeAllEffects();
		$this->extinguish();
		$this->setFood($this->getMaxFood());
		$this->setSaturation($this->getMaxSaturation());
		$this->setHealth($this->getMaxHealth());
	}

	/**
	 * @return float
	 */
	public function getMaxSaturation(): float {
		return $this->getAttributeMap()->getAttribute(Attribute::SATURATION)->getMaxValue();
	}

	public function load(): void {
		$this->loadData();
		$this->loadElo();
		$this->reset();
		$this->setNoClip(false);
		PracticeBase::getInstance()->addLobbyItems($this);
		$this->getScoreboard()->send(TextFormat::RED . " Valiant" . TextFormat::WHITE . " - " . "Practice ", Scoreboard::SLOT_SIDEBAR, Scoreboard::SORT_ASCENDING);
	}

	public function logOut(): void {
		$plugin = PracticeBase::getInstance();
		if($this->inMatch()) {
			$this->getMatch()->leave($this);
		}
		if ($this->inQueue()) {
			$this->removeFromQueue();
		}
		if ($plugin->getPartyManager()->hasInviteObject($this)) {
			$plugin->getPartyManager()->removeHostObject($this);
		}
		if ($this->inParty()) {
			$this->getParty()->remove($this);
		}
	}
}