<?php

/**
 * Copyright (C) 2018-2022  CzechPMDevs
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace czechpmdevs\buildertools\item;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\item\Item;

/**
 * Fake enchantment used to make BuilderTools items glow
 */
final class ItemGlint {
	private const GLINT_ENCHANTMENT_ID = -1;

	private static ?Enchantment $enchantment = null;

	public static function register(): void {
		if(self::$enchantment !== null) {
			return;
		}

		self::$enchantment = new Enchantment("", Rarity::COMMON, ItemFlags::NONE, ItemFlags::NONE, 1);
		EnchantmentIdMap::getInstance()->register(self::GLINT_ENCHANTMENT_ID, self::$enchantment);
	}

	public static function apply(Item $item): Item {
		self::register();

		/** @phpstan-var Enchantment $enchantment */
		$enchantment = self::$enchantment;
		return $item->addEnchantment(new EnchantmentInstance($enchantment));
	}
}
