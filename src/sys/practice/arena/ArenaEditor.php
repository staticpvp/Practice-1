<?php


namespace sys\practice\arena;


use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;

class ArenaEditor {

	/** @var Arena */
	private $arena;

	/** @var PracticePlayer */
	private $editor;

	/** @var int */
	public const FIRST_EDGE = 0;
	/** @var int */
	public const SECOND_EDGE = 1;
	/** @var int */
	public const FIRST_SPAWN = 2;
	/** @var int */
	public const SECOND_SPAWN = 3;
	/** @var int */
	public const MAX_BUILD_HEIGHT = 4;
	/** @var int */
	public const TYPE = 5;

	/** @var array */
	private $types = [];

	/** @var int */
	private $mode = self::FIRST_EDGE;

	/**
	 * ArenaEditor constructor.
	 * @param PracticePlayer $editor
	 */
	public function __construct(PracticePlayer $editor) {
		$this->editor = $editor;
		/*Setup arena with default values. These will be overridden afterwards*/
		$this->arena = new Arena(-1, [], [], $editor->getLevel(), -1, -1, false);
		$this->types = [
			"TERRAIN" => "These type of maps are used for maps you can build on",
			"FLAT" => "These type of maps are usually reserved for kits that have speed",
			"SG" => "These types of maps are dedicated to the SG kit"
		];
	}

	/**
	 * @return PracticePlayer
	 */
	public function getEditor(): PracticePlayer {
		return $this->editor;
	}

	/**
	 * @return Arena
	 */
	public function getArena(): Arena {
		return $this->arena;
	}

	/**
	 * @return array
	 */
	public function getTypes(): array {
		return $this->types;
	}

	/**
	 * @return int
	 */
	public function getMode(): int {
		return $this->mode;
	}

	public function advanceMode(): void {
		$this->mode += 1;
	}

	public function finish(): void {
		$manager = PracticeBase::getInstance()->getArenaManager();
		$this->getArena()->setId($manager->getNextArenaIndex());
		$this->getEditor()->addTitle(TextFormat::GREEN . "Arena added!", TextFormat::WHITE . "The arena was successfully created!", -1, 20 * 3, -1);
		$this->getArena()->saveChunks();
		$manager->addArena($this->getArena());
		$manager->cancelEditing($this->getEditor());
		$this->destroy();
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function handleBreak(BlockBreakEvent $event) {
		$block = $event->getBlock();
		$event->setCancelled();
		switch($this->getMode()) {
			case self::FIRST_EDGE:
				$this->getArena()->setLevel($block->getLevel());
				$this->getArena()->setEdge(0, $block);
				$this->getEditor()->sendMessage(TextFormat::GREEN . "First edge set to: ({$block->getX()}, {$block->getY()}, {$block->getZ()})");
				break;
			case self::SECOND_EDGE:
				$this->getArena()->setEdge(1, $block);
				$this->getEditor()->sendMessage(TextFormat::GREEN . "Second edge set to: ({$block->getX()}, {$block->getY()}, {$block->getZ()})");
				break;
			case self::FIRST_SPAWN:
				$this->getArena()->setPosition(0, $block);
				$this->getEditor()->sendMessage(TextFormat::GREEN . "First spawn set to: ({$block->getX()}, {$block->getY()}, {$block->getZ()})");
				break;
			case self::SECOND_SPAWN:
				$this->getArena()->setPosition(1, $block);
				$this->getEditor()->sendMessage(TextFormat::GREEN . "Second spawn set to: ({$block->getX()}, {$block->getY()}, {$block->getZ()})");
				break;
			case self::MAX_BUILD_HEIGHT:
				$this->getArena()->setMaxBuildHeight($block->getY());
				$this->getEditor()->sendMessage(TextFormat::GREEN . "Max build height set to: {$block->getY()}");
				break;
		}
		$this->advanceMode();
		$this->handleMessages();
	}

	/**
	 * @param PlayerChatEvent $event
	 */
	public function handleChat(PlayerChatEvent $event) {
		$event->setCancelled();
		if($this->getMode() === self::TYPE) {
			$msg = $event->getMessage();
			foreach($this->getTypes() as $name => $value) {
				if(stripos($msg, $name) !== false) {
					$this->getArena()->setType(array_search($name, array_keys($this->getTypes())));
					$this->getEditor()->sendMessage(TextFormat::GREEN . "Successfully set type to {$name}!");
					$this->finish();
					return;
				}
			}
			$this->getEditor()->sendMessage(TextFormat::RED . "Invalid type! Try again!");
		}
	}

	public function handleMessages(): void {
		switch($this->getMode()) {
			case self::FIRST_EDGE:
				$this->getEditor()->sendMessage(TextFormat::YELLOW . "To start, break one edge of the arena!");
				break;
			case self::SECOND_EDGE:
				$this->getEditor()->sendMessage(TextFormat::YELLOW . "Now, break the other edge of the arena! This edge should not be on the same Y level as the first edge!");
				break;
			case self::FIRST_SPAWN:
				$this->getEditor()->sendMessage(TextFormat::YELLOW . "Now, break the first player spawn!");
				break;
			case self::SECOND_SPAWN:
				$this->getEditor()->sendMessage(TextFormat::YELLOW . "Now, break the second player spawn!");
				break;
			case self::MAX_BUILD_HEIGHT:
				$this->getEditor()->sendMessage(TextFormat::YELLOW . "Now, break a block at the height of the desired max build height!");
				break;
			case self::TYPE:
				$this->getEditor()->sendMessage(TextFormat::YELLOW . "Now, type in chat the type of the arena. Here's a guide to the types");
				$this->getEditor()->sendMessage(TextFormat::YELLOW . "----- Types -----");
				foreach($this->getTypes() as $name => $message) {
					$this->getEditor()->sendMessage(TextFormat::YELLOW . $name . ": " . $message);
				}
		}
	}

	public function destroy(): void {
		foreach($this as $key => $value) {
			unset($this->$key);
		}
	}
}