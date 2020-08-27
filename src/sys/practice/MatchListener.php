<?php
/**
 * Created by PhpStorm.
 * User: Matthew
 * Date: 2/10/2017
 * Time: 9:48 PM
 */

namespace sys\practice;


use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use sys\jordan\core\base\BaseListener;


class MatchListener extends BaseListener {


	/**
	 * MatchListener constructor.
	 * @param PracticeBase $plugin
	 */
	public function __construct(PracticeBase $plugin) {
		parent::__construct($plugin);
	}

	/**
	 * @param ProjectileHitEvent $event
	 */
	public function onHit(ProjectileHitEvent $event): void {
		$entity = $event->getEntity();
		$player = $entity->getOwningEntity();
		if ($player instanceof PracticePlayer) {
			if ($player instanceof PracticePlayer and $player->inMatch() and $entity instanceof Arrow && !$entity->isClosed()) {
				$entity->kill();
			}
		}
	}

	/**
	 * @param CraftItemEvent $event
	 */
	public function onCraft(CraftItemEvent $event): void {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer and $player->inMatch()) {
			$event->setCancelled();
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onInteract(PlayerInteractEvent $event): void {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer and $player->inMatch()) {
			$player->getMatch()->onInteract($event);
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event) {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer and $player->inMatch()) {
			$player->getMatch()->onBreak($event);
		}
	}

	/**
	 * @param BlockPlaceEvent $event
	 */
	public function onPlace(BlockPlaceEvent $event): void {
		$player = $event->getPlayer();
		if ($player instanceof PracticePlayer and $player->inMatch()) {
			$player->getMatch()->onPlace($event);
		}
	}

	/**
	 * @param EntityRegainHealthEvent $event
	 */
	public function onRegainHealth(EntityRegainHealthEvent $event): void {
		$player = $event->getEntity();
		if ($player instanceof PracticePlayer and $player->inMatch()) {
			$player->getMatch()->onRegainHealth($event);
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 */
	public function onDamage(EntityDamageEvent $event): void {
		$player = $event->getEntity();
		if ($player instanceof PracticePlayer and $player->inMatch()) {
			$player->getMatch()->onDamage($event);
		}
	}

	/**
	 * @param PlayerDeathEvent $event
	 */
	public function onDeath(PlayerDeathEvent $event) {
		$player = $event->getPlayer();
		if($player instanceof PracticePlayer && !$player->inMatch()) {
			$event->setDrops([]);
		}
	}

}