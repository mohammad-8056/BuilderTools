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
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\World;
use function array_values;
use function count;
use function pack;
use function strlen;
use function unpack;

/**
 * Block storage which keeps blocks packed in binary strings (12 bytes per block)
 * instead of PHP arrays (~32 bytes per block), so large selections fit into memory.
 */
final class BlockArray {
	/** Blocks written to packed strings at once */
	public const BUFFER_SIZE = 4096;

	/** Block hashes (World::blockHash()) packed as signed 64-bit integers */
	protected string $coords = "";
	/** Block state ids packed as unsigned 32-bit integers */
	protected string $blocks = "";

	/** @var int[] */
	protected array $coordsBuffer = [];
	/** @var int[] */
	protected array $blocksBuffer = [];

	/** @var array<int, true>|null */
	protected ?array $knownHashes = null;

	public function __construct(bool $detectDuplicates = false) {
		if($detectDuplicates) {
			$this->knownHashes = [];
		}
	}

	/**
	 * Adds block to the block array
	 *
	 * @return $this
	 */
	public function addBlock(Vector3 $vector3, int $fullBlockId): BlockArray {
		return $this->addBlockAt($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ(), $fullBlockId);
	}

	/**
	 * Adds block to the block array
	 *
	 * @return $this
	 */
	public function addBlockAt(int $x, int $y, int $z, int $fullBlockId): BlockArray {
		$hash = World::blockHash($x, $y, $z);

		if($this->knownHashes !== null) {
			if(isset($this->knownHashes[$hash])) {
				return $this;
			}
			$this->knownHashes[$hash] = true;
		}

		$this->coordsBuffer[] = $hash;
		$this->blocksBuffer[] = $fullBlockId;

		if(count($this->blocksBuffer) === self::BUFFER_SIZE) {
			$this->flush();
		}

		return $this;
	}

	/**
	 * Writes buffered blocks to the packed strings
	 */
	protected function flush(): void {
		if(count($this->blocksBuffer) === 0) {
			return;
		}

		$this->coords .= pack("q*", ...$this->coordsBuffer);
		$this->blocks .= pack("N*", ...$this->blocksBuffer);

		$this->coordsBuffer = [];
		$this->blocksBuffer = [];
	}

	public function size(): int {
		return (strlen($this->blocks) >> 2) + count($this->blocksBuffer);
	}

	/**
	 * Returns packed block hashes (signed 64-bit integers)
	 */
	public function getPackedCoords(): string {
		$this->flush();
		return $this->coords;
	}

	/**
	 * Returns packed block state ids (unsigned 32-bit big endian integers)
	 */
	public function getPackedBlocks(): string {
		$this->flush();
		return $this->blocks;
	}

	public function setPackedData(string $coords, string $blocks): void {
		if(strlen($coords) !== strlen($blocks) * 2) {
			throw new AssumptionFailedError("Coords and blocks data size does not match");
		}

		$this->coords = $coords;
		$this->blocks = $blocks;
		$this->coordsBuffer = $this->blocksBuffer = [];
		$this->knownHashes = null;
	}

	/**
	 * Replaces content of this block array with the content of another one
	 */
	public function replaceWith(BlockArray $blockArray): void {
		$this->setPackedData($blockArray->getPackedCoords(), $blockArray->getPackedBlocks());
	}

	/**
	 * Adds Vector3 to all the blocks in BlockArray
	 */
	public function addVector3(Vector3 $vector3): BlockArray {
		$floorX = $vector3->getFloorX();
		$floorY = $vector3->getFloorY();
		$floorZ = $vector3->getFloorZ();

		$blockArray = new BlockArray();

		$iterator = new BlockArrayIteratorHelper($this);
		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullBlockId);
			$blockArray->addBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $fullBlockId);
		}

		return $blockArray;
	}

	/**
	 * Subtracts Vector3 from all the blocks in BlockArray
	 */
	public function subtractVector3(Vector3 $vector3): BlockArray {
		return $this->addVector3($vector3->multiply(-1));
	}

	/**
	 * @param int[] $blocks
	 */
	public function setBlockArray(array $blocks): void {
		$this->flush();
		$this->blocks = count($blocks) === 0 ? "" : pack("N*", ...$blocks);
	}

	/**
	 * Unpacks all the blocks to PHP array. Uses a lot of memory, use BlockArrayIteratorHelper instead.
	 *
	 * @return int[]
	 */
	public function getBlockArray(): array {
		$this->flush();
		if($this->blocks === "") {
			return [];
		}

		/** @var int[]|false $blocks */
		$blocks = unpack("N*", $this->blocks);
		if($blocks === false) {
			throw new AssumptionFailedError("Could not unpack blocks");
		}

		return array_values($blocks);
	}

	/**
	 * @param int[] $coords
	 */
	public function setCoordsArray(array $coords): void {
		$this->flush();
		$this->coords = count($coords) === 0 ? "" : pack("q*", ...$coords);
	}

	/**
	 * Unpacks all the coords to PHP array. Uses a lot of memory, use BlockArrayIteratorHelper instead.
	 *
	 * @return int[]
	 */
	public function getCoordsArray(): array {
		$this->flush();
		if($this->coords === "") {
			return [];
		}

		/** @var int[]|false $coords */
		$coords = unpack("q*", $this->coords);
		if($coords === false) {
			throw new AssumptionFailedError("Could not unpack coords");
		}

		return array_values($coords);
	}

	/**
	 * @return array{string, string}
	 */
	public function __serialize(): array {
		return [$this->getPackedCoords(), $this->getPackedBlocks()];
	}

	/**
	 * @param array{string, string} $data
	 */
	public function __unserialize(array $data): void {
		$this->coordsBuffer = $this->blocksBuffer = [];
		$this->setPackedData($data[0], $data[1]);
	}
}
