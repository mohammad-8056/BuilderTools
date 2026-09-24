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

namespace czechpmdevs\buildertools\blockstorage;

use pocketmine\nbt\tag\CompoundTag;
use function strlen;

/**
 * Immutable snapshot of BlockArray
 */
class CompressedBlockArray {
	protected string $compressedBlocks;
	protected string $compressedCoords;

	public function __construct(BlockArray $blockArray) {
		// Strings are copy-on-write, so no memory is copied here
		$this->compressedCoords = $blockArray->getPackedCoords();
		$this->compressedBlocks = $blockArray->getPackedBlocks();
	}

	public function getSize(): int {
		return strlen($this->compressedBlocks) >> 2;
	}

	public function asBlockArray(): BlockArray {
		$blockArray = new BlockArray();
		$blockArray->setPackedData($this->compressedCoords, $this->compressedBlocks);

		return $blockArray;
	}

	public function nbtSerialize(): CompoundTag {
		$nbt = new CompoundTag();
		$nbt->setByteArray("Coords", $this->compressedCoords);
		$nbt->setByteArray("Blocks", $this->compressedBlocks);

		return $nbt;
	}

	public static function nbtDeserialize(CompoundTag $nbt): self {
		$instance = new self(new BlockArray());
		$instance->compressedCoords = $nbt->getByteArray("Coords");
		$instance->compressedBlocks = $nbt->getByteArray("Blocks");

		return $instance;
	}
}
