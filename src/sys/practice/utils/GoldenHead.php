<?php
/**
 *
 * This file was created by Matt on 7/17/2017
 * Any attempts to copy, steal, or use this code
 * without permission will result in various consequences.
 *
 */

namespace sys\practice\utils;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\GoldenApple;

class GoldenHead extends GoldenApple {

	/**
	 * GoldenHead constructor.
	 * @param int $meta
	 */
	public function __construct($meta = 0) {
		parent::__construct($meta);

	}

	/**
	 * @return EffectInstance[]
	 */
	public function getAdditionalEffects(): array {
		return [
			new EffectInstance(Effect::getEffect(Effect::REGENERATION),20 * ($this->getDamage() == 1 ? 10 : 5), 1),
			new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 2400)
		];
	}

}