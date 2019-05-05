<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Arguments\ArgumentFactory;

use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;
use Webmozart\Assert\Assert;
use Addiks\SymfonyGenerics\Arguments\Argument;

final class ArgumentFactoryAggregate implements ArgumentFactory
{

    /**
     * @var array<ArgumentFactory>
     */
    private $innerArgumentFactories;

    public function __construct(array $innerArgumentFactories)
    {
        Assert::null($this->innerArgumentFactories);
        $this->innerArgumentFactories = array();

        foreach ($innerArgumentFactories as $innerArgumentFactory) {
            /** @var ArgumentFactory $innerArgumentFactory */

            Assert::isInstanceOf($innerArgumentFactory, ArgumentFactory::class);

            $this->innerArgumentFactories[] = $innerArgumentFactory;
        }
    }

    public function understandsString(string $source): bool
    {
        /** @var bool $understandsString */
        $understandsString = false;

        foreach ($this->innerArgumentFactories as $innerArgumentFactory) {
            /** @var ArgumentFactory $innerArgumentFactory */

            $understandsString = $innerArgumentFactory->understandsString($source);

            if ($understandsString) {
                break;
            }
        }

        return $understandsString;
    }

    public function understandsArray(array $source): bool
    {
        /** @var bool $understandsArray */
        $understandsArray = false;

        foreach ($this->innerArgumentFactories as $innerArgumentFactory) {
            /** @var ArgumentFactory $innerArgumentFactory */

            $understandsArray = $innerArgumentFactory->understandsArray($source);

            if ($understandsArray) {
                break;
            }
        }

        return $understandsArray;
    }

    public function createArgumentFromString(string $source): Argument
    {
        /** @var Argument $argument */
        $argument = null;

        foreach ($this->innerArgumentFactories as $innerArgumentFactory) {
            /** @var ArgumentFactory $innerArgumentFactory */

            if ($innerArgumentFactory->understandsString($source)) {
                $argument = $innerArgumentFactory->createArgumentFromString($source);
                break;
            }
        }

        Assert::isInstanceOf($argument, Argument::class, sprintf(
            "Could not parse '%s' into argument!",
            $source
        ));

        return $argument;
    }

    public function createArgumentFromArray(array $source): Argument
    {
        /** @var Argument $argument */
        $argument = null;

        foreach ($this->innerArgumentFactories as $innerArgumentFactory) {
            /** @var ArgumentFactory $innerArgumentFactory */

            if ($innerArgumentFactory->understandsArray($source)) {
                $argument = $innerArgumentFactory->createArgumentFromArray($source);
                break;
            }
        }

        Assert::isInstanceOf($argument, Argument::class, "Could not parse array into argument!");

        return $argument;
    }

}
