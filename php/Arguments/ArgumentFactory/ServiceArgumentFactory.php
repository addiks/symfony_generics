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
use Addiks\SymfonyGenerics\Arguments\Argument;
use Addiks\SymfonyGenerics\Arguments\ServiceArgument;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use Addiks\SymfonyGenerics\Arguments\LiteralArgument;

final class ServiceArgumentFactory implements ArgumentFactory
{

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function understandsString(string $source): bool
    {
        return strpos($source, '@') === 0 && strlen($source) > 1;
    }

    public function understandsArray(array $source): bool
    {
        return isset($source['service-id']);
    }

    public function createArgumentFromString(string $source): Argument
    {
        Assert::startsWith($source, '@');

        /** @var string $serviceId */
        $serviceId = substr($source, 1);

        return new ServiceArgument($this->container, new LiteralArgument($serviceId));
    }

    public function createArgumentFromArray(array $source): Argument
    {
        Assert::keyExists($source, 'service-id');

        return new ServiceArgument($this->container, $source['service-id']);
    }

}
