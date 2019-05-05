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
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use Addiks\SymfonyGenerics\Arguments\Argument;

final class ArgumentFactoryLazyLoadProxy implements ArgumentFactory
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var ArgumentFactory|null
     */
    private $loadedArgumentFactory;

    public function __construct(
        ContainerInterface $container,
        string $serviceId
    ) {
        $this->container = $container;
        $this->serviceId = $serviceId;
    }

    public function understandsString(string $source): bool
    {
        return $this->loadInnerArgumentFactory()->understandsString($source);
    }

    public function understandsArray(array $source): bool
    {
        return $this->loadInnerArgumentFactory()->understandsArray($source);
    }

    public function createArgumentFromString(string $source): Argument
    {
        return $this->loadInnerArgumentFactory()->createArgumentFromString($source);
    }

    public function createArgumentFromArray(array $source): Argument
    {
        return $this->loadInnerArgumentFactory()->createArgumentFromArray($source);
    }

    private function loadInnerArgumentFactory(): ArgumentFactory
    {
        if (is_null($this->loadedArgumentFactory)) {
            $this->loadedArgumentFactory = $this->container->get($this->serviceId);

            Assert::isInstanceOf($this->loadedArgumentFactory, ArgumentFactory::class, sprintf(
                "Expected service '%s' to be instance of '%s'!",
                $this->serviceId,
                ArgumentFactory::class
            ));
        }

        return $this->loadedArgumentFactory;
    }

}
