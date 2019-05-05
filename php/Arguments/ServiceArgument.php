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

namespace Addiks\SymfonyGenerics\Arguments;

use Addiks\SymfonyGenerics\Arguments\Argument;
use Psr\Container\ContainerInterface;

final class ServiceArgument implements Argument
{

    /**
     * @var Argument
     */
    private $serviceId;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface $container,
        Argument $serviceId
    ) {
        $this->container = $container;
        $this->serviceId = $serviceId;
    }

    public function resolve()
    {
        /** @var string $serviceId */
        $serviceId = $this->serviceId->resolve();

        return $this->container->get($serviceId);
    }

}
