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

use czechpmdevs\buildertools\blockstorage\helpers\BlockArrayIteratorHelper;
use pocketmine\math\Vector3;

final class BlockArraySizeData {
	private BlockArray $blockArray;

	public int $maxX, $maxY, $maxZ;
	public int $minX, $minY, $minZ;

	public function __construct(BlockArray $blockArray) {
		$this->blockArray = $blockArray;
		$this->calculateSizeData();
	}

	private function calculateSizeData(): void {
		if($this->blockArray->size() === 0) {
			return;
		}

		$iterator = new BlockArrayIteratorHelper($this->blockArray);

		$iterator->readNext($x, $y, $z, $fullBlockId);

		$minX = $maxX = $x;
		$minY = $maxY = $y;
		$minZ = $maxZ = $z;

		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullBlockId);
			if($x < $minX) {
				$minX = $x;
			} elseif($x > $maxX) {
				$maxX = $x;
			}
			if($y < $minY) {
				$minY = $y;
			} elseif($y > $maxY) {
				$maxY = $y;
			}
			if($z < $minZ) {
				$minZ = $z;
			} elseif($z > $maxZ) {
				$maxZ = $z;
			}
		}

		$this->minX = $minX;
		$this->minY = $minY;
		$this->minZ = $minZ;

		$this->maxX = $maxX;
		$this->maxY = $maxY;
		$this->maxZ = $maxZ;
	}

	/**
	 * Recalculates dimensions of the BlockArray
	 */
	public function recalculate(): void {
		$this->calculateSizeData();
	}

	public function getMinimum(): Vector3 {
		return new Vector3($this->minX, $this->minY, $this->minZ);
	}

	public function getMaximum(): Vector3 {
		return new Vector3($this->maxX, $this->maxY, $this->maxZ);
	}
}