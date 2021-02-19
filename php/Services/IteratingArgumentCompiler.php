<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Services;

use Addiks\SymfonyGenerics\Services\IteratingArgumentCompilerInterface;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Webmozart\Assert\Assert;

final class IteratingArgumentCompiler implements IteratingArgumentCompilerInterface
{

    /** @var ArgumentCompilerInterface */
    private $argumentCompiler;

    /** @var mixed */
    private $itemArguments;

    /** @var bool */
    private $argumentIsArray;

    public function __construct(
        ArgumentCompilerInterface $argumentCompiler,
        $itemArguments,
        bool $argumentIsArray = false
    ) {
        Assert::null($this->argumentCompiler);

        if ($argumentIsArray) {
            Assert::isArray($itemArguments);
        }

        $this->argumentCompiler = $argumentCompiler;
        $this->itemArguments = $itemArguments;
        $this->argumentIsArray = $argumentIsArray;
    }

    public function compileIterativeArgument(
        array $items,
        array $additionalData = array()
    ): array {
        /** @var array<int, mixed> $compiledItems */
        $compiledItems = array();

        /** @var mixed $item */
        foreach ($items as $key => $item) {
            /** @var array<string, mixed> $additionalItemData */
            $additionalItemData = array_merge($additionalData, ['item' => $item]);

            if (is_array($item)) {
                $additionalItemData = array_merge($additionalItemData, $item);
            }

            /** @var mixed $compiledItem */
            $compiledItem = null;

            if ($this->argumentIsArray) {
                $compiledItem = $this->argumentCompiler->buildArguments(
                    $this->itemArguments,
                    $additionalItemData
                );

            } else {
                $compiledItem = $this->argumentCompiler->buildArgument(
                    $this->itemArguments,
                    $additionalItemData
                );
            }

            $compiledItems[$key] = $compiledItem;
        }

        return $compiledItems;
    }

}
