<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 6:57 PM
 */

namespace sys\practice\arena;


use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use sys\practice\PracticePlayer;
use sys\practice\PracticeBase;

class ArenaManager {

	const TERRAIN = 0;
	const FLAT = 1;
	const SG = 2;

	/** @var PracticeBase */
	private $plugin;

	/** @var Config */
	private $config;

	/** @var Arena[] */
	private $arenas = [];

	/** @var ArenaEditor[] */
	private $editors = [];

	/**
	 * ArenaManager constructor.
	 * @param PracticeBase $main
	 */
	public function __construct(PracticeBase $main) {
		$main->saveResource("arenas.yml");
		$this->config = new Config($main->getDataFolder() . "arenas.yml", Config::YAML);
		$this->plugin = $main;
		//$this->findDuplicates();
		$this->load();
		new ArenaEditorListener($main);
	}

	public function save(): void {
		foreach($this->getArenas() as $arena) {
			$arenas = $this->getConfig()->get("arenas");
			if (!isset($arenas[$arena->getId()])) {
				$arenas[$arena->getId()] = $arena->toYAML();
				$this->getConfig()->set("arenas", $arenas);
				$this->getConfig()->save();
			}
		}
	}

	/**
	 * @return bool
	 */
	public function load(): bool {
		$arenas = $this->getConfig()->get("arenas");
		if (!empty($arenas)) {
			foreach($arenas as $id => $unparsedArena) {
				if (!$this->getPlugin()->getServer()->isLevelLoaded($unparsedArena["levelName"])) {
					$this->getPlugin()->getServer()->loadLevel($unparsedArena["levelName"]);
				}
				$level = $this->getPlugin()->getServer()->getLevelByName($unparsedArena["levelName"]);
				for ($i = 1; $i <= 2; $i++) {
					${"pos$i"} = new Position($unparsedArena["pos$i"][0], $unparsedArena["pos$i"][1], $unparsedArena["pos$i"][2], $level);
					${"edge$i"} = new Position($unparsedArena["edge$i"][0], $unparsedArena["edge$i"][1], $unparsedArena["edge$i"][2], $level);
				}
				if (isset($pos1, $pos2, $edge1, $edge2)) {
					$this->createArena($id, $pos1, $pos2, $edge1, $edge2, $level, $unparsedArena["type"], $unparsedArena["maxBuildHeight"]);
				} else {
					$this->getPlugin()->getLogger()->error(TextFormat::RED . "The positions have been corrupted!");
				}
			}
			$this->getPlugin()->getLogger()->info(TextFormat::GREEN . "The arenas have been loaded! Number of arenas: " . count($this->getArenas()));
			return true;
		} else {
			$this->getPlugin()->getLogger()->info(TextFormat::RED . "There are no arenas to load!");
		}
		return false;
	}

	public function findDuplicates(): void {
		$arenas = $this->getConfig()->get("arenas");
		$loaded = [];
		$includedKeys = ["pos1", "pos2", "edge1", "edge2"];
		$index = 1;
		$duplicate = false;
		foreach($arenas as $arena) {
			if(count($loaded) > 0) {
				$loadedIndex = 1;
				foreach($loaded as $loadedArena) {
					foreach($arena as $key => $value) {
						foreach($loadedArena as $loadedKey => $loadedValue) {
							if(in_array($key, $includedKeys) && in_array($loadedKey, $includedKeys) && $value === $loadedValue) {
								$this->getPlugin()->getLogger()->info(TextFormat::YELLOW . "Duplicate found!");
								if(is_array($value)) $value = "(" . implode(", ", $value) . ")";
								if(is_array($loadedValue)) $loadedValue = "(" . implode(", ", $loadedValue) . ")";
								$this->getPlugin()->getLogger()->info(TextFormat::YELLOW . "$key => $value equals $loadedKey => $loadedValue");
								$this->getPlugin()->getLogger()->info("Arena index: $index");
								$this->getPlugin()->getLogger()->info("Loaded index: $loadedIndex");
							}
						}
					}
					$loadedIndex++;
				}
			}
			$loaded[] = $arena;
			$index++;
		}
	}

	public function onDisable(): void {
		foreach ($this->getArenas() as $arena) {
			$arena->resetArena();
		}
		$this->getPlugin()->getLogger()->info(TextFormat::GREEN . "The arenas have been reset!");
		$this->save();
		$this->getPlugin()->getLogger()->info(TextFormat::GREEN . "The arenas have been saved!");
	}

	/**
	 * @return int
	 */
	public function getNextArenaIndex(): int {
		return count($this->arenas);
	}

	public function addArena(Arena $arena): void {
		$this->arenas[$arena->getId()] = $arena;
	}

	/**
	 * @param int $index
	 * @param Position $pos1
	 * @param Position $pos2
	 * @param Position $edge1
	 * @param Position $edge2
	 * @param Level $level
	 * @param int $type
	 * @param int $maxBuildHeight
	 * @return bool
	 *
	 * Returns true if the arena is created, false if not.
	 */
	public function createArena(int $index, Position $pos1, Position $pos2, Position $edge1, Position $edge2, Level $level, int $type, int $maxBuildHeight): bool {
		if($pos1->isValid() and $pos2->isValid() and $edge1->isValid() and $edge2->isValid()) {
			$arena = new Arena($index, array($pos1, $pos2), array($edge1, $edge2), $level, $type, $maxBuildHeight);
			$this->addArena($arena);
			return true;
		}
		return false;
	}

	/**
	 * @param int $index
	 * @return bool
	 */
	public function removeArena(int $index): bool {
		if ($this->getArenaById($index) !== null) {
			unset($this->arenas[$index]);
			return true;
		}
		return false;
	}

	/**
	 * @param int $index
	 * @return string
	 */
	public function deleteArena(int $index): string {
		$arenas = $this->getConfig()->get("arenas");
		if (isset($arenas[$index]) and isset($this->arenas[$index])) {
			unset($arenas[$index]);
			$this->removeArena($index);
			$arenas = array_values($arenas);
			$this->reorderArenas();
			$this->getConfig()->set("arenas", $arenas);
			$this->getConfig()->save();
			return TextFormat::GREEN . "Arena successfully deleted!";
		}
		return TextFormat::RED . "Arena could not be deleted!";
	}

	public function reorderArenas(): void {
		$this->arenas = array_values($this->arenas);
		foreach ($this->getArenas() as $index => $arena) {
			if ($arena->getId() !== $index) {
				$arena->setId($index);
			}
		}
	}

	/**
	 * @param int $index
	 * @return Arena|null
	 */
	public function getArenaById(int $index) {
		return $this->arenas[$index] ?? null;
	}

	/**
	 * @return Arena[]
	 */
	public function getArenas(): array {
		return $this->arenas;
	}

	public function getConfig(): Config {
		return $this->config;
	}

	/**
	 * @return Arena[]
	 */
	public function getOpenArenas(): array {
		$arenas = [];
		if (count($this->getArenas()) > 0) {
			foreach ($this->getArenas() as $arena) {
				if (!$arena->inUse()) {
					$arenas[] = $arena;
				}
			}
		}
		return $arenas;
	}

	/**
	 * @param int $type
	 * @return Arena|null
	 */
	public function getOpenArena(int $type): ?Arena {
		if (count($this->getOpenArenas()) > 0) {
			$typeArenas = [];
			foreach ($this->getOpenArenas() as $arena) {
				if ($arena->getType() === $type) {
					$typeArenas[] = $arena;
				}
			}
			if (count($typeArenas) > 0) {
				$arena = $typeArenas[array_rand($typeArenas, 1)];
				return $arena;
			}
		}
		return null;
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @param PracticePlayer $player
	 * @return bool
	 */
	public function isEditing(PracticePlayer $player): bool {
		return isset($this->editors[$player->getName()]);
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function setEditing(PracticePlayer $player): void {
		$this->editors[$player->getName()] = new ArenaEditor($player);
	}

	/**
	 * @param PracticePlayer $player
	 * @return ArenaEditor
	 */
	public function getEditor(PracticePlayer $player): ArenaEditor {
		return $this->editors[$player->getName()];
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function cancelEditing(PracticePlayer $player): void {
		if($this->isEditing($player)) {
			unset($this->editors[$player->getName()]);
		}
	}
}