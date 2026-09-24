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

namespace czechpmdevs\buildertools\blockstorage\identifiers;

use czechpmdevs\buildertools\utils\BlockStateConverter;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;

class SingleBlockIdentifier implements BlockIdentifierList {
	protected int $typeId;

	/**
	 * @param int  $stateId    Block state id
	 * @param bool $exactState If false, all the states of the block type are matched
	 */
	public function __construct(
		protected int $stateId,
		protected bool $exactState = true
	) {
		$this->typeId = BlockStateConverter::getTypeId($stateId);
	}

	public static function fromBlock(Block $block, bool $exactState = true): SingleBlockIdentifier {
		return new SingleBlockIdentifier($block->getStateId(), $exactState);
	}

	public function nextBlock(?int &$fullBlockId): void {
		$fullBlockId = $this->stateId;
	}

	public function containsBlock(int $fullBlockId): bool {
		return $this->exactState ? $fullBlockId === $this->stateId : BlockStateConverter::getTypeId($fullBlockId) === $this->typeId;
	}

	public function containsBlockId(int $id): bool {
		return $this->typeId === $id;
	}

	public static function airIdentifier(): SingleBlockIdentifier {
		return SingleBlockIdentifier::fromBlock(VanillaBlocks::AIR());
	}
}
