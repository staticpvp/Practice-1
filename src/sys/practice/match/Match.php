<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 7:11 PM
 */

namespace sys\practice\match;


use pocketmine\block\Block;
use pocketmine\block\Planks;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\FlintSteel;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\level\sound\AnvilBreakSound;
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\level\sound\Sound;
use pocketmine\utils\TextFormat;
use sys\practice\arena\Arena;
use sys\practice\arena\ArenaManager;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\kit\Kit;
use sys\practice\utils\BossBar;

class Match {

	/** @var PracticeBase */
	protected $plugin;

	/** @var int */
	protected $countdown = 10;

	/** @var int */
	protected $time = 60 * 15;

	/** @var int */
	protected $finishTime = 5;

	/** @var int */
	public const COUNTDOWN = 0;
	/** @var int */
	public const PLAYING = 1;
	/** @var int */
	public const POSTGAME = 2;

	/** @var int */
	private $state = self::COUNTDOWN;

	/** @var Arena */
	protected $arena;

	/** @var BossBar */
	protected $bossBar;

	/** @var string */
	private $id = "";

	/** @var Kit */
	protected $kit;

	/** @var PracticePlayer[] */
	protected $players = [];

	/** @var PracticePlayer[] */
	protected $matchPlayers = [];

	/** @var PracticePlayer[] */
	protected $spectators = [];

	/** @var PracticePlayer */
	private $winner = null;

	/** @var Position[] */
	protected $positions = [];
	/**
	 * Match constructor.
	 * @param PracticeBase $plugin
	 * @param Kit $kit
	 * @param PracticePlayer[] $players
	 * @param Arena $arena
	 */
	public function __construct(PracticeBase $plugin, Kit $kit, array $players, Arena $arena) {
		$this->plugin = $plugin;
		$this->arena = $arena;
		$this->id = uniqid("", true);
		$this->bossBar = new BossBar($plugin);
		$this->kit = $kit;
		foreach($players as $player) {
			$this->join($player);
		}
	}

	/**
	 * @param PracticePlayer $player
	 * @return array
	 */
	public function getScoreboardData(PracticePlayer $player): array {
		if($this->isPlayer($player)) {
			$data =  [
				1 => TextFormat::WHITE . "Match Time: " . TextFormat::RED . $this->getFormattedTime(),
				2 => "",
				3 => TextFormat::WHITE . "Ladder: " . TextFormat::RED . $this->getKit()->getName(),
				4 => "",
				6 => "",
				7 => TextFormat::WHITE . "CPS: " . TextFormat::RED . $player->getClicksPerSecond(),
				8 => TextFormat::WHITE . "Ping: " . TextFormat::RED . $player->getPing()
			];
			if($this->getOtherPlayer($player) !== null) {
				$data[5] = TextFormat::WHITE . "Opponent: " . TextFormat::RED . $this->getOtherPlayer($player)->getName();
			}
			return $data;
		}
		return [
			1 => TextFormat::WHITE . "Match Time: " . str_repeat(" ", 8),
			2 => str_repeat(" ", 2) . TextFormat::RED . $this->getFormattedTime(),
			3 => "",
			4 => TextFormat::WHITE . "Ladder: ",
			5 => str_repeat(" ", 2) . TextFormat::RED . $this->getKit()->getName(),
		];
	}

	public function teleportPlayers(): void {
		$this->loadLevel();
		foreach ($this->getPlayers() as $player) {
			$position = Position::fromObject($this->getMatchPosition($player)->add(0, 2), $this->getMatchPosition($player)->getLevel());
			$player->teleport($position);
		}
	}

	public function loadLevel(): void {
		if(!$this->getPlugin()->getServer()->isLevelLoaded($this->getArena()->getLevel()->getFolderName())) {
			$this->getPlugin()->getServer()->loadLevel($this->getArena()->getLevel()->getFolderName());
		}
	}

	/**
	 * @return int
	 */
	public function getState(): int {
		return $this->state;
	}

	/**
	 * @param int $state
	 */
	public function setState(int $state): void {
		$this->state = $state;
	}

	/**
	 * @param PracticePlayer $player
	 * @param int $index
	 */
	public function setMatchPosition(PracticePlayer $player, int $index): void {
		$this->positions[$player->getName()] = $this->getArena()->getPosition($index);
	}

	/**
	 * @param PracticePlayer $player
	 * @return Position|null
	 */
	public function getMatchPosition(PracticePlayer $player): ?Position {
		return $this->positions[$player->getName()] ?? null;
	}


	public function setMatchPositions(): void {
		$chunkSize = ceil(count($this->getPlayers()) / 2);
		$split = array_chunk($this->getPlayers(), $chunkSize);
		for ($i = 0; $i <= 1; $i++) {
			foreach ($split[$i] as $player) {
				$this->setMatchPosition($player, $i);
			}
		}
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function sendNameTags(PracticePlayer $player): void {
		$player->setCustomNameTag(TextFormat::GREEN . $player->getName(), [$player]);
		$player->setCustomNameTag(TextFormat::RED . $player->getName(), $this->getAllOtherPlayers($player));
	}

	/**
	 * @param PracticePlayer $player
	 * @return PracticePlayer[]
	 */
	public function getAllOtherPlayers(PracticePlayer $player): array {
		return array_filter($this->matchPlayers, function (PracticePlayer $arenaPlayer) use($player): bool {
			return $arenaPlayer->getName() !== $player->getName();
		});
	}

	public function shufflePlayers(): void {
		$keys = array_keys($this->players);
		shuffle($keys);
		$new = [];
		foreach ($keys as $key) {
			$new[$key] = $this->players[$key];
		}
		$this->players = $new;
	}

	public function init(): void {
		if (count($this->players) <= 1) {
			$this->reset();
			return;
		}
		$this->shufflePlayers();
		foreach ($this->getPlayers() as $player) {
			$player->reset(PracticePlayer::SURVIVAL);
			$this->getBossBar()->addBossBar($player);
			$this->getBossBar()->setBossBarProgress(20);
		}
		$this->sendScoreboard(true);
		$this->setMatchPositions();
		$this->getBossBar()->setBossBarTitle(TextFormat::WHITE . "Starting in " . TextFormat::RED . gmdate("i:s", $this->countdown) . TextFormat::WHITE . "...");
		$this->teleportPlayers();
	}


	/**
	 * @return Arena
	 */
	public function getArena(): Arena {
		return $this->arena;
	}

	/**
	 * @return BossBar
	 */
	public function getBossBar(): BossBar {
		return $this->bossBar;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return Kit
	 */
	public function getKit(): Kit {
		return $this->kit;
	}

	/**
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function isPlayer(PracticePlayer $player): bool {
		return isset($this->players[$player->getName()]);
	}

	/**
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function addPlayer(PracticePlayer $player): bool {
		if(!isset($this->players[$player->getName()])) {
			$this->players[$player->getName()] = $player;
			$this->matchPlayers[$player->getName()] = $player;
			return true;
		}
		return false;
	}

	/**
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function join(PracticePlayer $player): bool {
		if(!$this->hasStarted()) {
			if($this->addPlayer($player)) {
				$player->reset(PracticePlayer::SURVIVAL);
				$player->setMatch($this);
				return true;
			}
		} else {
			if($this->addSpectator($player)) {
				$player->setMatch($this);
				$player->reset(PracticePlayer::SPECTATOR);
				return true;
			}
		}
		return false;
	}

	/**
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function removePlayer(PracticePlayer $player): bool {
		if(isset($this->players[$player->getName()])) {
			unset($this->players[$player->getName()]);
			return true;
		}
		return false;
	}

	/**
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function leave(PracticePlayer $player): bool {
		if ($this->isSpectator($player)) {
			$this->removeSpectator($player);
			return true;
		} else if($this->removePlayer($player)) {
			$lastCause = $player->getLastDamageCause();
			if ($lastCause instanceof EntityDamageByEntityEvent) {
				$damager = $lastCause->getDamager();
				if($damager instanceof PracticePlayer) {
					$this->broadcastArgsMessage(TextFormat::RED . "{0} killed by {1} ({3} hearts)", $player->getName(), $damager->getName(), floor($damager->getHealth() / 2));
				}
			} else {
				$this->broadcastArgsMessage(TextFormat::RED . "{0} died", $player->getName());
			}
			$this->handleDeath($player, true);
			return true;
		}
		return false;
	}

	/**
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function isSpectator(PracticePlayer $player): bool {
		return isset($this->spectators[$player->getName()]);
	}

	/**
	 * @param PracticePlayer $player
	 * @param bool $addEffects
	 * @return bool
	 */
	public function addSpectator(PracticePlayer $player, $addEffects = false): bool {
		if(!isset($this->spectators[$player->getName()])) {
			$this->spectators[$player->getName()] = $player;
			$player->setSpectating($addEffects);
			$player->getScoreboard()->clearLines();
			return true;
		}
		return false;
	}

	/**
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function removeSpectator(PracticePlayer $player): bool {
		if(isset($this->spectators[$player->getName()])) {
			unset($this->spectators[$player->getName()]);
			return true;
		}
		return false;
	}

	/**
	 * @return PracticePlayer[]
	 */
	public function getAll(): array {
		return array_filter(array_merge($this->players, $this->spectators), function($player): bool {
			return $player instanceof PracticePlayer;
		});
	}

	/**
	 * @return PracticePlayer[]
	 */
	public function getPlayers(): array {
		return $this->players;
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @return bool
	 */
	public function hasStarted(): bool {
		return $this->getState() === self::PLAYING;
	}

	/**
	 * @return string
	 */
	public function getFormattedTime(): string {
		return gmdate("i:s", $this->time);
	}

	public function tick(int $currentTick): void {
		$this->sendScoreboard(false);
		if($currentTick % 20 === 0) {
			$this->update();
		}
	}

	public function update(): void {
		switch($this->getState()) {
			case self::COUNTDOWN:
				$this->getBossBar()->setBossBarTitle(TextFormat::WHITE . "Starting in " . TextFormat::RED . gmdate("i:s", $this->countdown) . TextFormat::WHITE . "...");
				switch($this->countdown) {
					case 10:
						$this->broadcastTitle(TextFormat::WHITE . "Match starting in:", TextFormat::GREEN . "{$this->countdown}...", 1, 10, 1);
						break;
					case 0:
						foreach ($this->getPlayers() as $player) {
							if ($player->isOnline()) {
								$this->sendNameTags($player);
								$player->reset(PracticePlayer::SURVIVAL);
								$this->broadcastSound(new AnvilBreakSound($player));
								$player->removeTitles();
								$player->teleport($this->getMatchPosition($player)->add(0, 2));
								$this->getKit()->giveKit($player);
							}
						}
						$this->sendFightingMessage();
						$this->setState(self::PLAYING);
						break;
					case $this->countdown <= 5:
						foreach ($this->getAll() as $player) {
							$this->broadcastSound(new AnvilFallSound($player, $this->countdown));
						}
						$this->broadcastMessage(TextFormat::GREEN . "Match starting in {$this->countdown}...");
						break;
				}
				$this->broadcastSubtitle(TextFormat::GREEN . "{$this->countdown}...");
				$this->countdown--;
				foreach ($this->getPlayers() as $player) {
					$position = $this->getMatchPosition($player);
					if ($position instanceof Position && $player->distance($position) >= 5) {
						$player->teleport($position->add(0, 2));
					}
				}
				break;
			case self::PLAYING:
				$this->getBossBar()->setBossBarTitle(TextFormat::WHITE . "Match Time: " . TextFormat::RED . $this->getFormattedTime());
				$this->time--;
				if($this->time % 15 === 0) {
					foreach ($this->getAll() as $player) {
						$this->getBossBar()->moveEntity($player);
					}
				}
				if ($this->time <= 0) {
					$this->broadcastMessage(TextFormat::RED . "Time ran out, so it's a draw!");
					$this->setState(self::POSTGAME);
				}
				break;
			case self::POSTGAME:
				$this->getBossBar()->setBossBarTitle(TextFormat::WHITE . "Ending match in: " . TextFormat::RED . gmdate("i:s", $this->finishTime));
				$this->finishTime--;
				if ($this->finishTime <= 0) {
					$this->reset();
				}
				break;
		}
	}

	/**
	 * @return bool
	 */
	public function isRanked(): bool {
		return false;
	}

	public function sendScoreboard(bool $clear = true): void {
		foreach ($this->getAll() as $player) {
			if($clear) $player->getScoreboard()->clearLines();
			$player->getScoreboard()->setLineArray($this->getScoreboardData($player));
		}
	}

	public function sendFightingMessage(): void {
		foreach ($this->getPlayers() as $player) {
			$againstMessage = TextFormat::RED . "Now in match against: ";
			foreach ($this->getAllOtherPlayers($player) as $otherPlayer) {
				$addElo = ($this->isRanked() ? "[" . $otherPlayer->getElo($this->getKit())->getElo() . "]" : "");
				$againstMessage .= $otherPlayer->getName() . $addElo . ", ";
			}

			$againstMessage = rtrim($againstMessage, ", ");
			$againstMessage .= " with kit " . $this->getKit()->getName();
			$player->sendMessage($againstMessage);
			if ($this->isRanked()) $player->sendMessage(TextFormat::RED . "Your Elo: " . $player->getElo($this->getKit())->getElo());

		}
	}

	/**
	 * @param PracticePlayer $player
	 * @return PracticePlayer|null
	 */
	public function getOtherPlayer(PracticePlayer $player): ?PracticePlayer {
		$others = $this->getAllOtherPlayers($player);
		$key = array_key_first($others);
		return $key !== null ? $others[$key] : null;
	}

	/**
	 * @param EntityDamageEvent $event
	 */
	public function onDamage(EntityDamageEvent $event): void {
		$entity = $event->getEntity();
		if($entity instanceof PracticePlayer) {
			if(!$this->hasStarted()) {
				$event->setCancelled();
			}
			if($event->getFinalDamage() >= $entity->getHealth() && !$event->isCancelled()) {
				if($event instanceof EntityDamageByEntityEvent) {
					$damager = $event->getDamager();
					if($damager instanceof PracticePlayer) {
						$this->broadcastMessage(TextFormat::RED . $entity->getName() . " killed by " . $damager->getName() . " (" . ((int)$damager->getHealth() / 2) . " hearts)");
					}
				} else {
					$lastCause = $entity->getLastDamageCause();
					if($lastCause instanceof EntityDamageByEntityEvent) {
						$damager = $lastCause->getDamager();
						if($damager instanceof PracticePlayer) {
							$this->broadcastMessage(TextFormat::RED . $entity->getName() . " killed by " . $damager->getName() . " (" . ((int)$damager->getHealth() / 2) . " hearts)");
						}
					} else {
						$this->broadcastMessage(TextFormat::RED . $entity->getName() . " died");
					}
				}
				$this->handleDeath($entity);
			} else {
				if($event instanceof EntityDamageByEntityEvent) {
					$damager = $event->getDamager();
					if($this->getKit()->isKit("Combo") and $entity->getGamemode() !== PracticePlayer::CREATIVE and $damager instanceof PracticePlayer) {
						$event->setKnockBack($event->getKnockBack() - ($event->getKnockBack() / 3.5));
						$event->setCancelled(false);
					}
				}
				if ($event instanceof EntityDamageByChildEntityEvent) {
					$child = $event->getChild();
					if ($child instanceof Arrow) {
						$shooter = $event->getDamager();
						if($shooter instanceof PracticePlayer) {
							if(($this->getKit()->getMapType() === ArenaManager::TERRAIN) || $this->getKit()->isKit("Archer")) {
								$shooter->sendArgsMessage(TextFormat::GREEN . $entity->getName() . " has {0} hearts left!", (round(($entity->getHealth() - $event->getFinalDamage()) / 2)));
							}
							$shooter->getLevel()->addSound(new AnvilFallSound($shooter->asPosition()), [$shooter]);
						}
					}

				}
			}
		}
	}

	/**
	 * @param EntityRegainHealthEvent $event
	 */
	public function onRegainHealth(EntityRegainHealthEvent $event): void {
		$entity = $event->getEntity();
		if ($entity instanceof PracticePlayer and !$this->getKit()->shouldRegen() && $event->getRegainReason() === EntityRegainHealthEvent::CAUSE_SATURATION) {
			$event->setCancelled();
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event): void {
		/** @var PracticePlayer $player */
		$player = $event->getPlayer();
		$item = $event->getItem();
		switch ($item->getId()) {
			case Item::BEETROOT_SOUP:
				if ($this->getKit()->isKit("BuffedSoup")) {
					if ($player->getHealth() < $player->getMaxHealth()) {
						$this->getKit()->addEffects($player, $item);
						$player->getInventory()->setItemInHand(Item::get(Item::AIR));
					}
				}
				break;
			case Item::MUSHROOM_STEW:
				if ($this->getKit()->isKit("IronSoup")) {
					if ($player->getHealth() < $player->getMaxHealth()) {
						$player->getInventory()->setItemInHand(Item::get(Item::AIR));
						$player->heal(new EntityRegainHealthEvent($player, 5, EntityRegainHealthEvent::CAUSE_CUSTOM));
					}
				}
				break;
			case Item::FLINT_AND_STEEL:
				if ($this->getKit()->isKit("SG") && $item instanceof FlintSteel) {
					$item->setDamage($item->getDamage() + $item->getMaxDurability() / 3);
					if ($item->getDamage() >= $item->getMaxDurability()) {
						$item->pop();
					}
				}
				break;
			default:
				$this->getKit()->addEffects($player, $item);
		}
	}

	/**
	 * @param BlockPlaceEvent $event
	 */
	public function onPlace(BlockPlaceEvent $event): void {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer) {
			$block = $event->getBlock();
			if ($block->getY() > $this->getArena()->getMaxBuildHeight()) {
				$event->setCancelled();
				$player->sendMessage(TextFormat::RED . "You can't build over the height limit!");
			}
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event): void {
		$player = $event->getPlayer();
		if ($this->getKit()->canBuild() and $player instanceof PracticePlayer and $player->inMatch()) {
			$id = $event->getBlock()->getId();
			if ($id !== Block::COBBLESTONE and $id !== Block::WOODEN_PLANKS) { //TODO: Whitelisted block lists
				$event->setCancelled();
			} else if ($id === Block::WOODEN_PLANKS and $event->getBlock()->getDamage() > Planks::OAK) {
				$event->setCancelled();
			}
		} else {
			$event->setCancelled();
		}
	}

	/**
	 * @param Sound $sound
	 */
	public function broadcastSound(Sound $sound): void {
		foreach ($this->getAll() as $player) {
			$sound->setComponents($player->getX(), $player->getY(), $player->getZ());
			$player->getLevel()->addSound($sound, [$player]);
		}
	}

	/**
	 * @param string $title
	 * @param string $subtitle
	 * @param int $fadeIn
	 * @param int $stay
	 * @param int $fadeOut
	 */
	public function broadcastTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1): void {
		foreach ($this->getAll() as $player) {
			$player->addTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
		}
	}

	/**
	 * @param string $subtitle
	 */
	public function broadcastSubtitle(string $subtitle) {
		foreach ($this->getAll() as $player) {
			$player->addSubTitle($subtitle);
		}
	}

	/**
	 * @param string $message
	 */
	public function broadcastMessage(string $message): void {
		foreach ($this->getAll() as $player) {
			$player->sendMessage($message);
		}
	}

	/**
	 * @param string $message
	 * @param $args
	 */
	public function broadcastArgsMessage(string $message, ...$args): void {
		foreach($this->getAll() as $player) {
			$player->sendArgsMessage($message, ...$args);
		}
	}

	/**
	 * @param string $message
	 */
	public function broadcastPopup(string $message): void {
		foreach ($this->getAll() as $player) {
			$player->sendPopup($message);
		}
	}

	/**
	 * @param string $message
	 * @param int $fadeIn
	 * @param int $stay
	 * @param int $fadeOut
	 */
	public function broadcastActionBar(string $message, int $fadeIn = -1, int $stay = -1, int $fadeOut = -1): void {
		foreach ($this->getAll() as $player) {
			$player->setTitleDuration($fadeIn, $stay, $fadeOut);
			$player->addActionBarMessage($message);
		}
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function setWinner(PracticePlayer $player): void {
		$this->winner = $player;
	}

	/**
	 * @return PracticePlayer|null
	 */
	public function getWinner(): ?PracticePlayer {
		return $this->winner;
	}

	/**
	 * @param PracticePlayer|null $player
	 * @param bool $leaving
	 */
	public function handleDeath(PracticePlayer $player, $leaving = false): void {
		if ($this->isPlayer($player)) {
			$this->removePlayer($player);
			if (!$leaving) {
				$this->addSpectator($player, true);
				$player->setHealth($player->getMaxHealth());
			}
			if (count($this->getPlayers()) > 1) {
				$player->dropAllItems();
			} else {
				$this->setState(self::POSTGAME);
				$player->getLevel()->dropItem($player->getPosition(), $player->getInventory()->getItemInHand());
				foreach ($this->getPlayers() as $arenaPlayer) {
					$arenaPlayer->setHealth($player->getMaxHealth());
					$arenaPlayer->setGamemode(PracticePlayer::CREATIVE);
					$this->setWinner($arenaPlayer);
				}
				foreach ($this->getAll() as $player) {
					$player->sendArgsMessage(TextFormat::GREEN . "Winner: {0}", $this->getWinner()->getName());
				}
			}
		}
	}

	public function reset(): void {
		foreach ($this->getAll() as $player) {
			$player->removeFromMatch();
			$player->reset();
			$this->getBossBar()->removeBossBar($player);
			if ($player->isFlying()) {
				$player->setFlying(false);
				$player->setAllowFlight(false);
			}
			$player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSafeSpawn());
			$this->getPlugin()->addLobbyItems($player);
		}
		$this->getArena()->resetArena();
		$this->kill();
	}

	public function kill(): void {
		$this->getPlugin()->getMatchManager()->removeMatch($this);
	}

	public function __destruct() {
		foreach($this as $key => $value) {
			unset($this->$key);
		}
	}

}