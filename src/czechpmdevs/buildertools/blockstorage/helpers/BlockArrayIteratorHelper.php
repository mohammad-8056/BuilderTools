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

namespace czechpmdevs\buildertools\blockstorage\helpers;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\World;
use function array_values;
use function min;
use function strlen;
use function substr;
use function unpack;

class BlockArrayIteratorHelper {
	protected int $lastHash;

	protected string $coords;
	protected string $blocks;
	protected int $size;

	/** @var int[] */
	protected array $coordsChunk = [];
	/** @var int[] */
	protected array $blocksChunk = [];
	protected int $chunkOffset = 0;
	protected int $chunkIndex = 0;
	protected int $chunkSize = 0;

	public function __construct(
		BlockArray $blockArray,
		protected int $offset = 0
	) {
		$this->coords = $blockArray->getPackedCoords();
		$this->blocks = $blockArray->getPackedBlocks();
		$this->size = strlen($this->blocks) >> 2;

		$this->loadChunk($this->offset);
	}

	/**
	 * Unpacks next part of the block array
	 */
	protected function loadChunk(int $offset): void {
		$this->chunkOffset = $offset;
		$this->chunkIndex = 0;
		$this->chunkSize = min(BlockArray::BUFFER_SIZE, $this->size - $offset);
		if($this->chunkSize <= 0) {
			$this->coordsChunk = $this->blocksChunk = [];
			$this->chunkSize = 0;
			return;
		}

		/** @var int[]|false $coords */
		$coords = unpack("q*", substr($this->coords, $offset << 3, $this->chunkSize << 3));
		/** @var int[]|false $blocks */
		$blocks = unpack("N*", substr($this->blocks, $offset << 2, $this->chunkSize << 2));
		if($coords === false || $blocks === false) {
			throw new AssumptionFailedError("Could not unpack block array");
		}

		$this->coordsChunk = array_values($coords);
		$this->blocksChunk = array_values($blocks);
	}

	/**
	 * Returns if it is possible read next block from the array
	 */
	public function hasNext(): bool {
		return $this->offset < $this->size;
	}

	/**
	 * Reads next block in the array
	 */
	public function readNext(?int &$x, ?int &$y, ?int &$z, ?int &$fullBlockId): void {
		if($this->chunkIndex >= $this->chunkSize) {
			$this->loadChunk($this->offset);
		}

		$this->lastHash = $this->coordsChunk[$this->chunkIndex];
		World::getBlockXYZ($this->lastHash, $x, $y, $z);
		$fullBlockId = $this->blocksChunk[$this->chunkIndex++];
		++$this->offset;
	}

	public function resetOffset(): void {
		$this->offset = 0;
		$this->loadChunk(0);
	}
}
