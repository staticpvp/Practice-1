<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 7:39 PM
 */

namespace sys\practice\kit;


use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use sys\practice\PracticePlayer;

class Kit {

	/** @var string */
	private $name;

	/** @var Item */
	private $icon;

	/** @var EffectInstance[] */
	private $effects = [];

	/** @var Item[] */
	private $armor = [];

	/** @var Item[] */
	private $items = [];

	/** @var int */
	private $mapType;

	/** @var bool */
	private $regenActive;

	/** @var bool */
	private $allowsBuilding;

	/**
	 * Kit constructor.
	 * @param string $name
	 * @param Item $icon
	 * @param array $armor
	 * @param array $items
	 * @param int $mapType
	 * @param bool $regenActive
	 * @param bool $allowsBuilding
	 */
	public function __construct(string $name, Item $icon, array $armor, array $items, int $mapType, bool $regenActive, bool $allowsBuilding) {
		$this->name = $name;
		$this->icon = $icon;
		$this->armor = $armor;
		$this->items = $items;
		$this->mapType = $mapType;
		$this->regenActive = $regenActive;
		$this->allowsBuilding = $allowsBuilding;
	}

	/**
	 * @return bool
	 */
	public function shouldRegen(): bool {
		return $this->regenActive;
	}

	/**
	 * @return bool
	 */
	public function canBuild(): bool {
		return $this->allowsBuilding;
	}

	/**
	 * @return array
	 */
	public function getArmor(): array {
		return $this->armor;
	}

	/**
	 * @return Item
	 */
	public function getIcon(): Item {
		return $this->icon;
	}

	/**
	 * @return array
	 */
	public function getItems(): array {
		return $this->items;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isKit(string $name): bool {
		return strtolower($this->getName()) == strtolower($name);
	}

	/**
	 * @return Effect[]
	 */
	public function getEffects(): array {
		return $this->effects;
	}

	/**
	 * @return int
	 */
	public function getMapType(): int {
		return $this->mapType;
	}

	/**
	 * @param EffectInstance $effect
	 */
	public function addEffect(EffectInstance $effect): void {
		if (!isset($this->effects[$effect->getId()])) {
			$this->effects[$effect->getId()] = $effect;
		}
	}

	/**
	 * @param PracticePlayer $player
	 */
	public function giveKit(PracticePlayer $player): void {
		$player->getInventory()->setContents($this->getItems());
		$player->getArmorInventory()->setContents($this->getArmor());
		if (count($this->getEffects()) > 0) {
			foreach ($this->getEffects() as $effect) $player->addEffect($effect);
		}
		$player->getInventory()->sendContents($player);
		$player->getArmorInventory()->sendContents($player);
	}

	/**
	 * @param Item $item
	 * @return bool
	 */
	public function hasEffects(Item $item) {
		return $item->getNamedTagEntry("effects") instanceof ListTag;
	}

	/**
	 * @param PracticePlayer $player
	 * @param Item $item
	 */
	public function addEffects(PracticePlayer $player, Item &$item) {
		if($this->hasEffects($item)) {
			$effects = $item->getNamedTagEntry("effects");
			if($effects instanceof ListTag) {
				foreach($effects->getValue() as $compound) {
					if($compound instanceof CompoundTag) {
						$id = $compound->getShort("id");
						$amplifier = $compound->getShort("amplifier");
						$duration = $compound->getShort("duration");
						$player->addEffect(new EffectInstance(Effect::getEffect($id), $duration,  $amplifier));
					}
				}
			}
		}
	}

}