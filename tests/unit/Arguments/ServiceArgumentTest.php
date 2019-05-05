<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\Arguments;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Arguments\ServiceArgument;
use Psr\Container\ContainerInterface;
use stdClass;
use Addiks\SymfonyGenerics\Arguments\Argument;

final class ServiceArgumentTest extends TestCase
{

    /**
     * @test
     */
    public function loremIpsum()
    {
        /** @var stdClass $sampleService */
        $sampleService = $this->createMock(stdClass::class);

        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->with(
            $this->equalTo('some-service-id')
        )->willReturn($sampleService);

        /** @var Argument $serviceId */
        $serviceId = $this->createMock(Argument::class);
        $serviceId->method('resolve')->willReturn("some-service-id");

        $argument = new ServiceArgument($container, $serviceId);

        /** @var mixed $actualResult */
        $actualResult = $argument->resolve();

        $this->assertSame($actualResult, $sampleService);
    }

}
