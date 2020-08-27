<?php
/**
 * Created by PhpStorm.
 * User: Matthew
 * Date: 2/10/2017
 * Time: 9:48 PM
 */

namespace sys\practice;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\item\Item;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use ReflectionException;
use sys\practice\match\event\MatchAddEvent;
use sys\practice\match\event\MatchRemoveEvent;
use sys\practice\match\Match;
use sys\practice\menu\defaults\DuelAcceptMenu;
use sys\practice\utils\ArenaChest;
use sys\practice\utils\ArenaChestInventory;
use sys\jordan\core\base\BaseListener;

class LobbyListener extends BaseListener {

	/**
	 * LobbyListener constructor.
	 * @param PracticeBase $plugin
	 */
	public function __construct(PracticeBase $plugin) {
		parent::__construct($plugin);
		$this->init();
	}



	private function init(): void {
		try {
			Tile::registerTile(ArenaChest::class);
		} catch (ReflectionException $e) {
			$this->getPlugin()->getLogger()->error($e->getMessage());
		}
	}

	/**
	 * @priority HIGHEST
	 * @param PlayerCreationEvent $event
	 */
	public function onCreation(PlayerCreationEvent $event): void {
		$event->setPlayerClass(PracticePlayer::class);
	}

	/**
	 * @param PlayerQuitEvent $event
	 */
	public function onQuit(PlayerQuitEvent $event): void {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer) {
			$player->logOut();
			/** @var PracticeBase $plugin */
			$plugin = $this->getPlugin();
			$plugin->updateScoreboards();
			$event->setQuitMessage(null);
		}
	}

	/**
	 * @param MatchAddEvent $event
	 */
	public function onMatchAdd(MatchAddEvent $event): void {
		/** @var PracticeBase $plugin */
		$plugin = $this->getPlugin();
		$plugin->updateScoreboards();
	}

	public function onMatchRemove(MatchRemoveEvent $event): void {
		/** @var PracticeBase $plugin */
		$plugin = $this->getPlugin();
		$plugin->updateScoreboards();
	}

	/**
	 * @param InventoryPickupItemEvent $event
	 */
	public function onPickup(InventoryPickupItemEvent $event): void {
		$inventory = $event->getInventory();
		if($inventory instanceof PlayerInventory) {
			$player = $inventory->getHolder();
			if ($player instanceof PracticePlayer) {
				if (!$player->getMatch() instanceof Match and !$player->isOp()) {
					$event->setCancelled();
				}
			}
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event): void {
		$player = $event->getPlayer();
		$action = $event->getAction();
		if ($action === PlayerInteractEvent::RIGHT_CLICK_AIR) {
			if ($player instanceof PracticePlayer && !$player->inMenu() && !$player->inMatch()) {
				$item = $event->getItem();
				/** @var PracticeBase $plugin */
				$plugin = $this->getPlugin();
				if ($plugin->getInteractionManager()->matchesInteraction($item, $player)) {
					$event->setCancelled();
				}
			}
		}
	}

	/**
	 * @param PlayerDeathEvent $event
	 */
	public function onDeath(PlayerDeathEvent $event): void {
		$event->setDeathMessage(null);
	}

	/**
	 * @param PlayerRespawnEvent $event
	 */
	public function onRespawn(PlayerRespawnEvent $event): void {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer) {
			/** @var PracticeBase $plugin */
			$plugin = $this->getPlugin();
			$plugin->addLobbyItems($player);
		}
	}

	/**
	 * @param PlayerDropItemEvent $event
	 */
	public function onDrop(PlayerDropItemEvent $event) {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer && !$player->inMatch()) {
			$event->setCancelled();
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 */
	public function onDamage(EntityDamageEvent $event): void {
		$player = $event->getEntity();
		if ($player instanceof PracticePlayer) {
			if(!$player->inMatch()) {
				$event->setCancelled();
				if($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
					$player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSafeSpawn());
				}
			}
		}
	}

	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event): void {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer) {
			$player->load();
			/** @var PracticeBase $plugin */
			$plugin = $this->getPlugin();
			$plugin->sendDefaultScoreboard($player);
			$plugin->updateScoreboards();
			$event->setJoinMessage(null);
		}
	}

	/**
	 * @param InventoryTransactionEvent $event
	 */
	public function onTransaction(InventoryTransactionEvent $event): void {
		$chestInventory = null;
		$transaction = null;
		$player = $event->getTransaction()->getSource();
		foreach ($event->getTransaction()->getInventories() as $inventory) {
			if ($inventory instanceof ArenaChestInventory) {
				$chestInventory = $inventory;
				break;
			}
		}
		foreach ($event->getTransaction()->getActions() as $action) {
			if (!$action or $action instanceof DropItemAction) {
				continue;
			}
			$transaction = $action;
		}
		$item = $transaction->getTargetItem()->getId() === Item::AIR ? $transaction->getSourceItem() : $transaction->getTargetItem();
		if ($player instanceof PracticePlayer and $chestInventory instanceof ArenaChestInventory and $item instanceof Item and $player->inMenu()) {
			$event->setCancelled();
			$player->getMenu()->getInteraction($player, $chestInventory, $item);
			$player->getCursorInventory()->clearAll();
		}

	}

	/**
	 * @param PlayerExhaustEvent $event
	 */
	public function onExhaust(PlayerExhaustEvent $event): void {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer and !$player->inMatch()) {
			$event->setCancelled();
		}
	}

	/**
	 * @param PlayerChatEvent $event
	 */
	public function onChat(PlayerChatEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof PracticePlayer) {
			if($player->inMatch()) {
				$event->setRecipients($player->getMatch()->getAll());
			} else {
				/** @var PracticeBase $plugin */
				$plugin = $this->getPlugin();
				$event->setRecipients($plugin->getLobbyPlayers());
			}
		}
	}

	/**
	 * @param InventoryCloseEvent $event
	 */
	public function onClose(InventoryCloseEvent $event): void {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer) {
			if ($player->inMenu()) {
				$menu = $player->getMenu();
				if ($menu instanceof DuelAcceptMenu and $player->hasDuelRequest()) { //accidental exit of menu
					$player->setHasDuelRequest(false);
					$player->sendArgsMessage(TextFormat::GREEN . "You have denied {0}'s duel request!", $menu->getOpponent()->getName());
					$menu->getOpponent()->sendArgsMessage(TextFormat::RED . "{0} has denied your duel request!", $player->getName());
				}
				$player->removeMenu();
			}
		}
	}

}