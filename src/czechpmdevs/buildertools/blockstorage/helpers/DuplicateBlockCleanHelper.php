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
use czechpmdevs\buildertools\BuilderTools;
use function array_combine;
use function array_keys;
use function array_reverse;
use function array_values;

class DuplicateBlockCleanHelper {
	public function cleanDuplicateBlocks(BlockArray $blockArray): void {
		if(!(BuilderTools::getConfiguration()->getBoolProperty("remove-duplicate-blocks"))) {
			return;
		}

		// Keeps the first occurrence of each position (it holds the original block when used for undo)
		$blocks = array_combine(array_reverse($blockArray->getCoordsArray()), array_reverse($blockArray->getBlockArray()));

		$cleaned = new BlockArray();
		$cleaned->setCoordsArray(array_reverse(array_keys($blocks)));
		$cleaned->setBlockArray(array_reverse(array_values($blocks)));
		$blockArray->replaceWith($cleaned);
	}
}
