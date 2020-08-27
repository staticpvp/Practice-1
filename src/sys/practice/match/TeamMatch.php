<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 7:11 PM
 */

namespace sys\practice\match;


use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\TextFormat;
use sys\practice\arena\Arena;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;
use sys\practice\kit\Kit;

class TeamMatch extends Match {

	/** @var Team[] */
	private $teams = [];

	/** @var Team */
	private $winningTeam = null;

	/**
	 * Match constructor.
	 * @param PracticeBase $plugin
	 * @param Kit $kit
	 * @param PracticePlayer[] $players
	 * @param Arena $arena
	 */
	public function __construct(PracticeBase $plugin, Kit $kit, array $players, Arena $arena) {
		parent::__construct($plugin, $kit, $players, $arena);
	}

	/**
	 * parent::init() would be nice here
	 */
	public function init(): void {
		$this->shufflePlayers();
		foreach ($this->getPlayers() as $player) {
			$player->reset(PracticePlayer::SURVIVAL);
			$player->setMatch($this);
			$this->getBossBar()->addBossBar($player);
			$this->getBossBar()->setBossBarProgress(20);
		}
		/** @var PracticePlayer[][] $split */
		$split = array_chunk($this->getPlayers(), ceil(count($this->getPlayers()) / 2));
		for ($i = 0; $i <= 1; $i++) {
			$team = new Team();
			$this->addTeam($i, $team);
			foreach ($split[$i] as $player) {
				$this->setTeam($player, $i);
				$this->setMatchPosition($player, $i);
				$this->getBossBar()->setBossBarTitle(TextFormat::WHITE . "Starting in " . TextFormat::RED . gmdate("i:s", $this->countdown) . TextFormat::WHITE . "...");
			}
		}
		$this->teleportPlayers();
	}

	/**
	 * @param PracticePlayer $player
	 * @param int $index
	 * @return bool
	 *
	 * Returns true if player is added successfully
	 */
	public function setTeam(PracticePlayer $player, int $index): bool {
		$team = $this->teams[$index];
		if ($team instanceof Team and !$team->hasPlayer($player)) {
			$team->addPlayer($player);
			return true;
		}
		return false;
	}

	/**
	 * @param int $index
	 * @param Team $team
	 */
	public function addTeam(int $index, Team $team): void {
		$this->teams[$index] = $team;
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function sendNameTags(PracticePlayer $player): void {
		foreach ($this->getTeams() as $team) {
			$opponentTeam = $this->getOtherTeam($team);
			if ($opponentTeam instanceof Team) {
				foreach ($team->getPlayers() as $player) {
					$player->setCustomNameTag(TextFormat::GREEN . $player->getName(), $team->getPlayers());
				}
				foreach ($opponentTeam->getPlayers() as $player) {
					$player->setCustomNameTag(TextFormat::RED . $player->getName(), $team->getPlayers());
				}
			}
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 */
	public function onDamage(EntityDamageEvent $event): void {
		$player = $event->getEntity();
		if ($player instanceof PracticePlayer and $event instanceof EntityDamageByEntityEvent) {
			$damager = $event->getDamager();
			if ($damager instanceof PracticePlayer and !$this->isSpectator()) {
				$team = $this->getTeam($damager);
				$otherTeam = $this->getTeam($player);
				if ($team instanceof Team and $otherTeam instanceof Team) {
					if ($team->onTeam($player, $damager) and $otherTeam->onTeam($player, $damager)) {
						$event->setCancelled();
					}
				}
			}
		}
		parent::onDamage($event);
	}

	/**
	 * @param Team $team
	 * @return null|Team
	 */
	public function getOtherTeam(Team $team): ?Team {
		foreach ($this->getTeams() as $matchTeam) {
			if ($team === $matchTeam) {
				continue;
			}
			return $matchTeam;
		}
		return null;
	}

	/**
	 * @return Team[]
	 */
	public function getTeams(): array {
		return $this->teams;
	}

	/**
	 * @param PracticePlayer $player
	 * @return null|Team
	 */
	public function getTeam(PracticePlayer $player): ?Team {
		foreach ($this->getTeams() as $team) {
			if ($team->hasPlayer($player)) {
				return $team;
			}
		}
		return null;
	}

	/**
	 * @param Team $team
	 */
	public function setWinningTeam(Team $team): void {
		$this->winningTeam = $team;
	}

	/**
	 * @return Team|null
	 */
	public function getWinningTeam(): ?Team {
		return $this->winningTeam;
	}

	/**
	 * @return Team|null
	 */
	public function checkTeams(): ?Team {
		foreach ($this->getTeams() as $team) {
			if ($team->isDead()) return $team;
		}
		return null;
	}

	public function sendFightingMessage(): void {
		foreach ($this->getTeams() as $team) {
			$oppositeTeamString = $this->getOtherTeam($team)->__toString();
			$team->sendArgsMessage(TextFormat::RED . "Now in match against: {0}", $oppositeTeamString);
		}
	}

	/**
	 * @param PracticePlayer|null $player
	 * @param bool $leaving
	 */
	public function handleDeath(PracticePlayer $player = null, $leaving = false): void {
		if ($this->isPlayer($player)) {
			$this->removePlayer($player);
			$team = $this->getTeam($player);
			if ($team instanceof Team) {
				$team->subtractPlayerCount();
			}
			$teamDead = $this->checkTeams();
			if ($teamDead instanceof Team) {
				$this->getArena()->getLevel()->dropItem($player->getPosition(), $player->getInventory()->getItemInHand());
				foreach ($this->getPlayers() as $arenaPlayer) {
					$arenaPlayer->setHealth($player->getMaxHealth());
					$arenaPlayer->setGamemode(PracticePlayer::CREATIVE);
					$this->setWinningTeam($this->getOtherTeam($team));
				}
				$this->setState(self::POSTGAME);
				$this->broadcastArgsMessage(TextFormat::GREEN . "Winners: {0}", $this->getWinningTeam()->__toString());
			} else {
				$player->dropAllItems();
			}
			if (!$leaving) $this->addSpectator($player, true);
		}
	}

	public function __destruct() {
		$this->teams = null;
		$this->winningTeam = null;
		parent::__destruct();
	}


	public function kill(): void {
		foreach ($this->getTeams() as $team) $team->nullify();
		parent::kill();
	}

}