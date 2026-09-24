<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\utils;

use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\StringToTParser;
use function is_string;

/**
 * Handles parsing blocks from strings.
 *
 * @phpstan-extends StringToTParser<Block>
 */
final class StringToBlockParser extends StringToTParser {
	use SingletonTrait;

	/** @phpstan-ignore-next-line */
	private static function make(): self {
		$result = new self;

		$result->register("air", fn() => VanillaBlocks::AIR());

		$itemParser = StringToItemParser::getInstance();
		foreach($itemParser->getKnownAliases() as $alias) {
			if(!is_string($alias)) {
				continue;
			}

			$item = $itemParser->parse($alias);
			if($item === null) {
				continue;
			}

			$block = $item->getBlock();
			if($block->getTypeId() === BlockTypeIds::AIR) {
				continue;
			}

			try {
				$result->register($alias, fn() => clone $block);
			} catch(InvalidArgumentException) {
			}
		}

		// Legacy numeric ids (e.g. "35:14" or "1")
		$registry = RuntimeBlockStateRegistry::getInstance();
		for($id = 0; $id < 256; ++$id) {
			for($meta = 0; $meta <= BlockStateConverter::LEGACY_META_MASK; ++$meta) {
				$stateId = BlockStateConverter::tryFromLegacy($id, $meta);
				if($stateId === null) {
					continue;
				}

				try {
					$result->register("$id:$meta", fn() => $registry->fromStateId($stateId));
				} catch(InvalidArgumentException) {
				}

				if($meta === 0) {
					try {
						$result->register((string)$id, fn() => $registry->fromStateId($stateId));
					} catch(InvalidArgumentException) {
					}
				}
			}
		}

		return $result;
	}

	public function parse(string $input): ?Block {
		return parent::parse($input);
	}
}
