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

namespace czechpmdevs\buildertools\utils;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use Throwable;

/**
 * Converts between PocketMine-MP 5 block state IDs and legacy (pre-1.13) numeric
 * block IDs (id << 4 | meta), which are still used by some schematic formats.
 */
final class BlockStateConverter {
	public const LEGACY_META_BITS = 4;
	public const LEGACY_META_MASK = 0xf;

	/** @var array<int, int> legacy full id => state id */
	private static array $legacyToState = [];
	/** @var array<int, int>|null state id => legacy full id */
	private static ?array $stateToLegacy = null;
	/** @var array<int, int> type id => legacy full id */
	private static array $typeToLegacy = [];

	public static function getTypeId(int $stateId): int {
		return $stateId >> Block::INTERNAL_STATE_DATA_BITS;
	}

	public static function getFallbackStateId(): int {
		return VanillaBlocks::INFO_UPDATE()->getStateId();
	}

	public static function fromLegacyFullId(int $legacyFullId): int {
		return self::fromLegacy($legacyFullId >> self::LEGACY_META_BITS, $legacyFullId & self::LEGACY_META_MASK);
	}

	/**
	 * Returns state id of the legacy block, or INFO_UPDATE state id if the block is unknown
	 */
	public static function fromLegacy(int $id, int $meta): int {
		return self::tryFromLegacy($id, $meta) ?? self::getFallbackStateId();
	}

	/**
	 * Returns state id of the legacy block, or null if the block is unknown
	 */
	public static function tryFromLegacy(int $id, int $meta): ?int {
		$legacyFullId = $id << self::LEGACY_META_BITS | ($meta & self::LEGACY_META_MASK);
		if(isset(self::$legacyToState[$legacyFullId])) {
			return self::$legacyToState[$legacyFullId] === -1 ? null : self::$legacyToState[$legacyFullId];
		}

		try {
			$stateData = GlobalBlockStateHandlers::getUpgrader()->upgradeIntIdMeta($id, $meta & self::LEGACY_META_MASK);
			$stateId = GlobalBlockStateHandlers::getDeserializer()->deserialize($stateData);
		} catch(BlockStateDeserializeException|UnsupportedBlockStateException) {
			$stateId = -1;
		}

		self::$legacyToState[$legacyFullId] = $stateId;
		return $stateId === -1 ? null : $stateId;
	}

	/**
	 * Converts block state NBT (as used in .mcstructure files or chunks) to state id
	 */
	public static function fromBlockStateNbt(CompoundTag $tag): int {
		try {
			$stateData = GlobalBlockStateHandlers::getUpgrader()->upgradeBlockStateNbt($tag);
			return GlobalBlockStateHandlers::getDeserializer()->deserialize($stateData);
		} catch(Throwable) {
			return self::getFallbackStateId();
		}
	}

	/**
	 * Returns legacy full id (id << 4 | meta) of the given state. If the state does not
	 * have legacy representation, returns legacy id of the same block type or INFO_UPDATE.
	 */
	public static function toLegacyFullId(int $stateId): int {
		self::loadReverseMapping();

		/** @phpstan-var array<int, int> $map */
		$map = self::$stateToLegacy;
		return $map[$stateId] ?? self::$typeToLegacy[self::getTypeId($stateId)] ?? (248 << self::LEGACY_META_BITS);
	}

	private static function loadReverseMapping(): void {
		if(self::$stateToLegacy !== null) {
			return;
		}

		self::$stateToLegacy = [];
		for($id = 0; $id < 256; ++$id) {
			for($meta = 0; $meta <= self::LEGACY_META_MASK; ++$meta) {
				$stateId = self::tryFromLegacy($id, $meta);
				if($stateId === null) {
					continue;
				}

				$legacyFullId = $id << self::LEGACY_META_BITS | $meta;
				self::$stateToLegacy[$stateId] ??= $legacyFullId;
				self::$typeToLegacy[self::getTypeId($stateId)] ??= $legacyFullId;
			}
		}
	}
}
