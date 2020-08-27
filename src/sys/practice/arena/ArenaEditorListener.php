<?php


namespace sys\practice\arena;


use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\plugin\Plugin;
use sys\practice\PracticeBase;
use sys\jordan\core\base\BaseListener;

class ArenaEditorListener extends BaseListener {

	/**
	 * ArenaEditorListener constructor.
	 * @param Plugin $plugin
	 */
	public function __construct(Plugin $plugin) {
		parent::__construct($plugin);
	}

	/**
	 * @param PlayerChatEvent $event
	 */
	public function handleChat(PlayerChatEvent $event): void {
		/** @var PracticeBase $plugin */
		$plugin = $this->getPlugin();
		if($plugin->getArenaManager()->isEditing($event->getPlayer())) {
			$plugin->getArenaManager()->getEditor($event->getPlayer())->handleChat($event);
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function handleBreak(BlockBreakEvent $event): void {
		/** @var PracticeBase $plugin */
		$plugin = $this->getPlugin();
		if($plugin->getArenaManager()->isEditing($event->getPlayer())) {
			$plugin->getArenaManager()->getEditor($event->getPlayer())->handleBreak($event);
		}
	}

}