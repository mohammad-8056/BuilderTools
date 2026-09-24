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

namespace czechpmdevs\buildertools\schematics\format;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\schematics\ReadonlySchematic;
use czechpmdevs\buildertools\schematics\SchematicException;
use czechpmdevs\buildertools\utils\BlockStateConverter;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use Throwable;
use function array_map;
use function intval;

/**
 * MCStructSchematic is schematic format created by Mojang for structure blocks
 * - It should have different extension (.mcstructure instead of .schematic)
 */
class MCStructureSchematic implements Schematic {
	use ReadonlySchematic;

	public function load(string $rawData): BlockArray {
		$nbt = (new LittleEndianNbtSerializer())->read($rawData)->mustGetCompoundTag();

		$size = $this->readVector3($nbt, "size");

		// Blocks
		$palette = $this->readPalette($nbt);
		$indexes = $this->readIndexArray($nbt);

		// Dimensions
		$width = $size->getFloorX();
		$height = $size->getFloorY();
		$length = $size->getFloorZ();

		$blockArray = new BlockArray();

		$i = 0;
		for($x = 0; $x < $width; ++$x) {
			for($y = 0; $y < $height; ++$y) {
				for($z = 0; $z < $length; ++$z) {
					$index = $indexes[$i];
					if($index !== -1) { // -1 = structure void
						$blockArray->addBlockAt($x, $y, $z, $palette[$index] ?? BlockStateConverter::getFallbackStateId());
					}
					++$i;
				}
			}
		}

		return $blockArray;
	}

	/**
	 * @throws SchematicException
	 */
	private function readVector3(CompoundTag $nbt, string $name): Vector3 {
		$tag = $nbt->getListTag($name);
		if($tag === null) {
			throw new SchematicException("List Tag $name was not found.");
		}

		return new Vector3(...array_map(fn(mixed $val) => intval($val), $tag->getAllValues()));
	}

	/**
	 * @return int[]
	 */
	private function readPalette(CompoundTag $nbt): array {
		/** @var CompoundTag[] $paletteData */
		$paletteData = $nbt->getCompoundTag("structure")->getCompoundTag("palette")->getCompoundTag("default")->getListTag("block_palette")->getValue(); // @phpstan-ignore-line (We provide validated values)

		$palette = [];
		foreach($paletteData as $i => $entry) {
			$palette[$i] = BlockStateConverter::fromBlockStateNbt($entry);
		}

		return $palette;
	}

	/**
	 * @return int[]
	 */
	private function readIndexArray(CompoundTag $nbt): array {
		/** @var ListTag $listTag */
		$listTag = $nbt->getCompoundTag("structure")->getListTag("block_indices")->get(0); // @phpstan-ignore-line (We provide valid values)
		// TODO : Find out why there is another list tag on index 1 full of -1 values

		/** @var int[] $values */
		$values = $listTag->getAllValues();
		return $values;
	}

	public static function getFileExtension(): string {
		return "mcstructure";
	}

	public static function validate(string $rawData): bool {
		try {
			$nbt = (new LittleEndianNbtSerializer())->read($rawData)->getTag();
			if(!$nbt instanceof CompoundTag) {
				return false;
			}

			// Test if palette exists
			$nbt->getCompoundTag("structure")->getCompoundTag("palette")->getCompoundTag("default")->getListTag("block_palette")->getAllValues(); // @phpstan-ignore-line (Errors are caught)

			// Test if block indexes exists
			$nbt->getCompoundTag("structure")->getListTag("block_indices")->get(0)->getValue(); // @phpstan-ignore-line (Errors are caught)

			return $nbt->getTag("size") instanceof ListTag && $nbt->getListTag("size")->count() === 3; // @phpstan-ignore-line (Errors are caught)
		} catch(Throwable) {
			return false;
		}
	}
}