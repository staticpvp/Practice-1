<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 6:29 PM
 */

namespace sys\practice\arena;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\utils\MainLogger;

class Arena {

	/** @var int */
	private $type;

	/** @var Level */
	private $level;

	/** @var int */
	private $id;

	/** @var int */
	private $maxBuildHeight;

	/** @var Position[] */
	private $edges = [];

	/** @var ArenaChunk[] */
	private $chunks = [];

	/** @var Position[] */
	private $positions = [];

	/** @var bool */
	private $inUse = false;

	/**
	 * Arena constructor.
	 * @param int $id
	 * @param Position[] $positions
	 * @param Position[] $edges
	 * @param Level $level
	 * @param int $type
	 * @param int $maxBuildHeight
	 * @param bool $saveChunks
	 */
	public function __construct(int $id, array $positions, array $edges, Level $level, int $type, int $maxBuildHeight, bool $saveChunks = true) {
		$this->type = $type;
		$this->id = $id;
		$this->level = $level;
		$level->setTime(1000);
		$level->stopTime();
		$this->edges = $edges;
		$this->maxBuildHeight = $maxBuildHeight;
		$this->positions = $positions;
		if($saveChunks) $this->saveChunks();
	}

	/**
	 * @return array
	 */
	public function toYAML(): array {
		return [
			"pos1" => $this->convertPosition($this->getPosition(0)),
			"pos2" =>$this->convertPosition($this->getPosition(1)),
			"edge1" => $this->convertPosition($this->getEdge(0)),
			"edge2" => $this->convertPosition($this->getEdge(1)),
			"levelName" => $this->getLevel()->getFolderName(),
			"type" => $this->getType(),
			"maxBuildHeight" => $this->getMaxBuildHeight()];
	}

	/**
	 * @param Position $position
	 * @return int[]
	 */
	public function convertPosition(Position $position): array {
		return array($position->getFloorX(), $position->getFloorY(), $position->getFloorZ());
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId(int $id): void {
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function getType(): int {
		return $this->type;
	}

	/**
	 * @param int $type
	 */
	public function setType(int $type): void {
		$this->type = $type;
	}

	/**
	 * @return Level
	 */
	public function getLevel(): Level {
		return $this->level;
	}

	/**
	 * @param Level $level
	 */
	public function setLevel(Level $level): void {
		$this->level = $level;
	}

	/**
	 * @param int $index
	 * @return Position
	 */
	public function getEdge(int $index): Position {
		return $this->edges[$index];
	}

	/**
	 * @return Position[]
	 */
	public function getEdges(): array {
		return $this->edges;
	}

	/**
	 * @param int $index
	 * @param Position $position
	 */
	public function setEdge(int $index, Position $position): void {
		$this->edges[$index] = $position;
	}

	/**
	 * @return Position
	 */
	public function getRandomPosition(): Position {
		return $this->getPosition(array_rand($this->positions, 1));
	}

	/**
	 * @param int $index
	 * @return Position
	 */
	public function getPosition(int $index): Position {
		return $this->positions[$index];
	}

	/**
	 * @param int $index
	 * @param Position $position
	 */
	public function setPosition(int $index, Position $position): void {
		$this->positions[$index] = $position;
	}

	/**
	 * @return Position[]
	 */
	public function getPositions(): array {
		return $this->positions;
	}

	/**
	 * @return bool
	 */
	public function inUse(): bool {
		return $this->inUse;
	}

	public function setInUse(bool $value = true): void {
		$this->inUse = $value;

		if ($value) {
			$this->prepareChunks();
		}
	}

	/**
	 * @return int
	 */
	public function getMaxBuildHeight(): int {
		return $this->maxBuildHeight;
	}

	/**
	 * @param int $maxBuildHeight
	 */
	public function setMaxBuildHeight(int $maxBuildHeight): void {
		$this->maxBuildHeight = $maxBuildHeight;
	}

	/**
	 * @return ArenaChunk[]
	 */
	public function getChunks(): array {
		return $this->chunks;
	}

	public function resetChunks(): void {
		$this->chunks = [];
	}

	/**
	 * @param Chunk $chunk
	 * @return bool
	 */
	public function chunkExists(Chunk $chunk): bool {
		return isset($this->chunks[Level::chunkHash($chunk->getX(), $chunk->getZ())]);
	}

	/**
	 * @param Chunk $chunk
	 */
	public function addChunk(Chunk $chunk): void {
		$this->chunks[$hash = Level::chunkHash($chunk->getX(),  $chunk->getZ())] = new ArenaChunk($this, $chunk);
		$this->getLevel()->registerChunkLoader($this->chunks[$hash], $chunk->getX(), $chunk->getZ());
	}

	public function saveChunks(): void {
		$this->resetChunks();
		$edge1 = $this->getEdge(0);
		$edge2 = $this->getEdge(1);
		$edgeMin = new Vector3(min($edge1->x, $edge2->x), min($edge1->y, $edge2->y), min($edge1->z, $edge2->z));
		$edgeMax = new Vector3(max($edge1->x, $edge2->x), max($edge1->y, $edge2->y), max($edge1->z, $edge2->z));
		for ($x = $edgeMin->getFloorX(); $x <= $edgeMax->getFloorX(); $x++) {
			for ($z = $edgeMin->getFloorZ(); $z <= $edgeMax->getFloorZ(); $z++) {
				$chunk = $this->getLevel()->getChunk($x >> 4, $z >> 4);
				if ($chunk !== null and !$this->chunkExists($chunk)) {
					$this->addChunk($chunk);
				}

			}
		}
		MainLogger::getLogger()->debug("Arena #" . ($this->getId() + 1) . " > " . count($this->getChunks()) . " chunks saved!");
	}

	public function prepareChunks(): void {
		$level = $this->getLevel();
		$chunkCount = 0;
		foreach ($this->chunks as $chunk) {
			$level->registerChunkLoader($chunk, $chunk->getChunkX(), $chunk->getChunkZ());
			$chunkCount++;
		}
		MainLogger::getLogger()->debug("Arena #" . ($this->getId() + 1) . " > " . $chunkCount . " chunks prepared!");
	}

	public function resetArena(): void {
		$this->setInUse(false);
		$level = $this->getLevel();
		$chunkCount = 0;
		foreach ($this->getChunks() as $chunk) {
			$level->unregisterChunkLoader($chunk, $chunk->getChunkX(), $chunk->getChunkZ());
			$level->setChunk($chunk->getChunkX(), $chunk->getChunkZ(), $chunk->getChunk(), true);
			$level->clearChunkCache($chunk->getChunkX(), $chunk->getChunkZ());
			$chunkCount++;
		}
		MainLogger::getLogger()->debug("Arena #" . ($this->getId() + 1) . " > " . $chunkCount . " chunks reset!");
	}

}