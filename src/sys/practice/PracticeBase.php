<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 6:14 PM
 */

namespace sys\practice;


use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use sys\practice\arena\ArenaManager;
use sys\practice\command\AddArenaCommand;
use sys\practice\command\DuelCommand;
use sys\practice\command\EditEloCommand;
use sys\practice\command\EloCommand;
use sys\practice\command\PartyCommand;
use sys\practice\command\SpectateCommand;
use sys\practice\interaction\InteractionManager;
use sys\practice\kit\KitManager;
use sys\practice\match\MatchManager;
use sys\practice\party\PartyManager;
use sys\practice\queue\QueueManager;
use sys\jordan\core\OverloadPatcher;
use function str_repeat;

class PracticeBase extends PluginBase {

	/** @var string */
	const FOLDER_NAME = "players";

	/** @var self */
	private static $instance;

	/** @var ArenaManager */
	private $arenaManager;

	/** @var QueueManager */
	private $queueManager;

	/** @var KitManager */
	private $kitManager;

	/** @var InteractionManager */
	private $interactionManager;

	/** @var MatchManager */
	private $matchManager;

	/** @var PartyManager */
	private $partyManager;

	/*
	 * Don't put the loaders into the onLoad function, as trying to start
	 * a task, before the plugin is fully enabled, will not work
	 */
	public function onEnable(): void {
		self::$instance = $this;
		$this->loadFolders();
		$this->loadManagers();
		$this->loadCommands();
		$this->loadListeners();
		$this->getServer()->getDefaultLevel()->setTime(14000);
		$this->getServer()->getDefaultLevel()->stopTime();
		$this->getLogger()->info(TextFormat::GREEN . $this->getDescription()->getName() . " has been enabled!");
	}

	public function onDisable(): void {
		$this->redirectPlayers();
		$this->getArenaManager()->onDisable();
		$this->getLogger()->info(TextFormat::RED . $this->getDescription()->getName() . " has been disabled!");
	}

	public function redirectPlayers(): void {
		foreach($this->getServer()->getOnlinePlayers() as $player) {
			$player->kick(TextFormat::RED . "Server stopping! Redirecting to hub...", false);
		}
	}

	private function loadManagers(): void {
		$this->arenaManager = new ArenaManager($this);
		$this->interactionManager = new InteractionManager($this);
		$this->kitManager = new KitManager($this);
		$this->matchManager = new MatchManager($this);
		$this->partyManager = new PartyManager($this);
		$this->queueManager = new QueueManager($this);
	}

	private function loadCommands(): void {
		$this->getServer()->getCommandMap()->registerAll("arenapvp", [
			new AddArenaCommand($this),
			new DuelCommand($this),
			new EditEloCommand($this),
			new EloCommand($this),
			new PartyCommand($this),
			new SpectateCommand($this)
		]);
		new OverloadPatcher($this, "commands.json");
	}

	private function loadListeners(): void {
		new LobbyListener($this);
		new MatchListener($this);
	}

	private function loadFolders(): void {
		@mkdir($this->getDataFolder() . self::FOLDER_NAME, 0777, true);
	}

	/**
	 * @return ArenaManager
	 */
	public function getArenaManager(): ArenaManager {
		return $this->arenaManager;
	}

	/**
	 * @return QueueManager
	 */
	public function getQueueManager(): QueueManager {
		return $this->queueManager;
	}

	/**
	 * @return KitManager
	 */
	public function getKitManager(): KitManager {
		return $this->kitManager;
	}

	/**
	 * @return InteractionManager
	 */
	public function getInteractionManager(): InteractionManager {
		return $this->interactionManager;
	}

	/**
	 * @return MatchManager
	 */
	public function getMatchManager(): MatchManager {
		return $this->matchManager;
	}

	/**
	 * @return PartyManager
	 */
	public function getPartyManager(): PartyManager {
		return $this->partyManager;
	}

	/**
	 * @return self
	 */
	public static function getInstance(): self {
		return self::$instance;
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function addLobbyItems(PracticePlayer $player): void {
		$player->getInventory()->setContents($this->getLobbyItems());
	}

	/**
	 * @return Item[]
	 */
	public function getLobbyItems(): array {
		$items = [
			[Item::DIAMOND_SWORD, TextFormat::AQUA."Join Ranked Queue"],
			[Item::GOLDEN_SWORD, TextFormat::DARK_AQUA."Join Unranked Queue"],
			[Item::EMPTY_MAP, TextFormat::LIGHT_PURPLE."Start Party Event"],
		];
		return array_map(function (array $item): Item {
			return Item::get($item[0])->setCustomName($item[1]);
		}, $items);
	}

	/**
	 * @return PracticePlayer[]
	 */
	public function getLobbyPlayers(): array {
		return array_filter($this->getServer()->getLoggedInPlayers(), function (PracticePlayer $player): bool {
			return !$player->inMatch();
		});
	}

	/**
	 * @return PracticePlayer[]
	 */
	public function getMatchPlayers(): array {
		return array_filter($this->getServer()->getLoggedInPlayers(), function (PracticePlayer $player): bool {
			return $player->inMatch();
		});
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function sendDefaultScoreboard(PracticePlayer $player): void {
		$player->getScoreboard()->clearLines();
		$pad = str_repeat(" ", 5);
		$line = str_repeat("-", 15);
		$player->getScoreboard()->setLineArray([
			0 => $line,
			1 => TextFormat::WHITE . "Players: " . TextFormat::YELLOW . count($this->getServer()->getLoggedInPlayers()) . $pad,
			2 => "",
			3 => TextFormat::WHITE . "In Lobby: " . TextFormat::YELLOW . count($this->getLobbyPlayers()) . $pad,
			4 => TextFormat::WHITE . "In Game: " . TextFormat::YELLOW . count($this->getMatchPlayers()) . $pad,
			5 => $line
		]);
	}

	public function updateScoreboards(): void {
		foreach($this->getLobbyPlayers() as $player) {
			$this->sendDefaultScoreboard($player);
		}
	}

}
