<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 2/10/2017
 * Time: 7:43 PM
 */

namespace sys\practice\kit;


use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use sys\practice\arena\ArenaManager;
use sys\practice\PracticeBase;
use sys\practice\utils\GoldenHead;

class KitManager {

	const LATEST_VERSION = 1;

	/** @var int */
	private $version;

	/** @var Config */
	private $config;

	/** @var Kit[] */
	private $kits = [];

	/** @var PracticeBase */
	private $plugin;

	/**
	 * KitManager constructor.
	 * @param PracticeBase $plugin
	 */
	public function __construct(PracticeBase $plugin) {
		$this->plugin = $plugin;
		if(!file_exists($plugin->getDataFolder() . DIRECTORY_SEPARATOR . "kits.json")) {
			$plugin->saveResource("kits.json", false);
		}
		$this->readConfig();
		ItemFactory::registerItem(new GoldenHead(), true);
		$this->loadKits();
	}

	public function readConfig(): void {
		$this->config = new Config($this->getPlugin()->getDataFolder() . "kits.json", Config::JSON);
		$this->version = $this->config->get("version") ?? 0;
		if($this->version < self::LATEST_VERSION) {
			$this->getPlugin()->getLogger()->info(TextFormat::RED . "Your kits.json is out of date, which might cause unexpected behavior!");
		}
		if(!$this->config->exists("kits")) {
			$this->getPlugin()->saveResource("kits.json", true);
			$this->getPlugin()->getLogger()->info(TextFormat::RED . "Your kits.json has been automatically reset due to unexpected errors!");
		}
	}

	public function loadKits(): void {
		if($this->getConfig()->exists("kits")) {
			foreach ($this->getConfig()->get("kits") as $kit => $data) {
				$armor = $this->parseArmor($data);
				$items = $this->parseItems($data);
				$icon = $data["icon"];
				if (is_array($icon)) {
					$iconItem = Item::get($icon[0], $icon[1]);
				} else {
					$iconItem = Item::get($icon);
				}
				$iconItem->setCustomName(TextFormat::GREEN . $kit);

				$kitClass = new Kit($kit, $iconItem, $armor, $items, $data["mapType"] ?? ArenaManager::TERRAIN, $data["shouldRegen"] ?? true, $data["allowsBuilding"] ?? false);
				if (isset($data["effects"]) and count($data["effects"]) > 0) {
					foreach ($data["effects"] as $effect) {
						if ($effect[2] == "infinite") {
							$effect[2] = INT32_MAX;
						}
						$effectInstance = new EffectInstance(Effect::getEffect($effect[0]), $effect[2], $effect[1]);
						$kitClass->addEffect($effectInstance);
					}
				}
				$this->addKit($kitClass);
			}
		}
		$this->getPlugin()->getLogger()->info(TextFormat::GREEN . "The kits have been loaded! Number of kits: " . count($this->getKits()));
	}

	/**
	 * @param Kit $kit
	 */
	public function addKit(Kit $kit): void {
		$this->kits[$kit->getName()] = $kit;
	}

	/**
	 * @param string $name
	 * @return null|Kit
	 */
	public function getKitByName(string $name): ?Kit {
		foreach ($this->getKits() as $kit) {
			if ($kit->isKit($name)) {
				return $kit;
			}
		}
		return null;
	}

	/**
	 * @param array $data
	 * @return Item[]
	 */
	public function parseItems(array $data): array {
		$parsedItems = [];
		foreach ($data["items"] as $item) {
			$parsedItem = Item::get($item["id"], $item["meta"]);
			if (isset($item["enchantments"])) {
				$this->addEnchantments($parsedItem, $item["enchantments"]);
			}
			if (isset($item["customName"])) {
				$parsedItem->setCustomName($item["customName"]);
			}
			if(isset($item["effects"])) {
				$this->addEffects($parsedItem, $item["effects"]);
			}
			if (($item["count"] > $parsedItem->getMaxStackSize()) or ($item["id"] == Item::AIR and $item["count"] > 1) or ($item["id"] == Item::SPLASH_POTION and $item["count"] > 1)) {
				for ($i = 1; $i <= $item["count"]; $i++) {
					$parsedItems[] = $parsedItem;
				}
			} else {
				$parsedItem->setCount($item["count"]);
				$parsedItems[] = $parsedItem;
			}
		}
		return $parsedItems;
	}

	/**
	 * @param array $data
	 * @return Item[]
	 */
	public function parseArmor(array $data): array {
		$parsedArmor = [];
		$armor[] = $data["helmet"] ?? [0];
		$armor[] = $data["chestplate"] ?? [0];
		$armor[] = $data["leggings"] ?? [0];
		$armor[] = $data["boots"] ?? [0];
		foreach ($armor as $item) {
			$parsedItem = Item::get($item[0]);
			if (isset($item[1])) {
				$this->addEnchantments($parsedItem, $item[1]);
			}
			$parsedArmor[] = $parsedItem;
		}
		return $parsedArmor;
	}

	/**
	 * @return Config
	 */
	public function getConfig(): Config {
		return $this->config;
	}

	/**
	 * @return Kit[]
	 */
	public function getKits(): array {
		return $this->kits;
	}

	/**
	 * @return PracticeBase
	 */
	public function getPlugin(): PracticeBase {
		return $this->plugin;
	}

	/**
	 * @return Item[]
	 */
	public function getAllKitItems(): array {
		$items = [];
		foreach ($this->getKits() as $kit) {
			$items[] = $kit->getIcon();
		}
		return $items;
	}

	/**
	 * @param Item $item
	 * @param array $enchantments
	 */
	public function addEnchantments(Item &$item, array $enchantments) {
		foreach ($enchantments as $enchantment) {
			$enchantmentObject = Enchantment::getEnchantment($enchantment[0]);
			if ($enchantmentObject instanceof Enchantment) {
				$enchantmentInstance = new EnchantmentInstance($enchantmentObject,  $enchantment[1]);
				$item->addEnchantment($enchantmentInstance);
			}
		}
	}

	/**
	 * @param Item $item
	 * @param array $effects
	 */
	public function addEffects(Item &$item, array $effects) {
		$tag = $item->getNamedTagEntry("effects");
		if(!($tag instanceof ListTag)){
			$tag = new ListTag("effects", []);
		}
		foreach($effects as $id => $data) {
			$effect = Effect::getEffect($id);
			if(!($effect instanceof Effect)) continue;
			$compound = new CompoundTag();
			$compound->setShort("id", $effect->getId());
			$compound->setShort("amplifier", $data["amplifier"] ?? 0);
			$compound->setShort("duration", $data["duration"] ?? $effect->getDefaultDuration());
			$tag->push($compound);
		}
		$item->setNamedTagEntry($tag);
	}

}
