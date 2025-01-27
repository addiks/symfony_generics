<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Arguments\ArgumentFactory;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ServiceArgumentFactory;
use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Arguments\ServiceArgument;
use Addiks\SymfonyGenerics\Arguments\LiteralArgument;

final class ServiceArgumentFactoryTest extends TestCase
{

    /**
     * @var ServiceArgumentFactory
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->factory = new ServiceArgumentFactory($this->container);
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldKnowIfUnderstandString
     */
    public function shouldKnowIfUnderstandString(bool $expectedResult, string $source)
    {
        /** @var bool $actualResult */
        $actualResult = $this->factory->understandsString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldKnowIfUnderstandString()
    {
        return array(
            [true, "@foo"],
            [true, "@f"],
            [false, "@"],
            [false, "foo"],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldKnowIfUnderstandArray
     */
    public function shouldKnowIfUnderstandArray(bool $expectedResult, array $source)
    {
        /** @var bool $actualResult */
        $actualResult = $this->factory->understandsArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldKnowIfUnderstandArray()
    {
        return array(
            [true, ["service-id" => 'foo']],
            [false, ["foo"]],
            [false, ["service-id" => null]],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldCreateArgumentFromString
     */
    public function shouldCreateArgumentFromString($expectedResult, string $source, bool $expectException)
    {
        if ($expectException) {
            $this->expectException(InvalidArgumentException::class);
        }

        /** @var bool $actualResult */
        $actualResult = $this->factory->createArgumentFromString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldCreateArgumentFromString()
    {
        $this->setUp();

        return array(
            [new ServiceArgument($this->container, new LiteralArgument('foo')), "@foo", false],
            [new ServiceArgument($this->container, new LiteralArgument('f')), "@f", false],
            [null, "@", true],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldCreateArgumentFromArray
     */
    public function shouldCreateArgumentFromArray($expectedResult, array $source, bool $expectException)
    {
        if ($expectException) {
            $this->expectException(InvalidArgumentException::class);
        }

        /** @var bool $actualResult */
        $actualResult = $this->factory->createArgumentFromArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldCreateArgumentFromArray()
    {
        $this->setUp();

        return array(
            [new ServiceArgument($this->container, new LiteralArgument('foo')), ["service-id" => 'foo'], false],
            [new ServiceArgument($this->container, new LiteralArgument('f')), ["service-id" => 'f'], false],
            [null, ["service-id" => null], true],
            [null, ["service-id"], true],
        );
    }

}
