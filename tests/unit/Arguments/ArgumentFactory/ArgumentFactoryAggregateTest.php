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
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactoryAggregate;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;
use stdClass;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Arguments\Argument;
use Addiks\SymfonyGenerics\Arguments\LiteralArgument;
use Addiks\SymfonyGenerics\Arguments\EntityArgument;

final class ArgumentFactoryAggregateTest extends TestCase
{

    /**
     * @var ArgumentFactoryAggregate
     */
    private $factoryAggregate;

    /**
     * @var ArgumentFactory
     */
    private $innerArgumentFactoryA;

    /**
     * @var ArgumentFactory
     */
    private $innerArgumentFactoryB;

    /**
     * @var ArgumentFactory
     */
    private $innerArgumentFactoryC;

    public function setUp(): void
    {
        $this->innerArgumentFactoryA = $this->createMock(ArgumentFactory::class);
        $this->innerArgumentFactoryB = $this->createMock(ArgumentFactory::class);
        $this->innerArgumentFactoryC = $this->createMock(ArgumentFactory::class);

        $this->factoryAggregate = new ArgumentFactoryAggregate([
            $this->innerArgumentFactoryA,
            $this->innerArgumentFactoryB,
            $this->innerArgumentFactoryC,
        ]);
    }

    /**
     * @test
     */
    public function shouldRejectNonArgumentFactoryInConstructor()
    {
        $this->expectException(InvalidArgumentException::class);

        new ArgumentFactoryAggregate([
            $this->innerArgumentFactoryA,
            $this->innerArgumentFactoryB,
            $this->createMock(stdClass::class),
        ]);
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldKnowWhenToUnderstandString
     */
    public function shouldKnowWhenToUnderstandString(bool $expectedResult, string $source, bool $a, bool $b, bool $c)
    {
        $this->innerArgumentFactoryA->expects($this->any())->method('understandsString')->with(
            $this->equalTo($source)
        )->willReturn($a);
        $this->innerArgumentFactoryB->expects($this->any())->method('understandsString')->with(
            $this->equalTo($source)
        )->willReturn($b);
        $this->innerArgumentFactoryC->expects($this->any())->method('understandsString')->with(
            $this->equalTo($source)
        )->willReturn($c);

        /** @var bool $actualResult */
        $actualResult = $this->factoryAggregate->understandsString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldKnowWhenToUnderstandString()
    {
        return array(
            [false, "foo", false, false, false],
            [true,  "foo", true,  false, false],
            [true,  "foo", false, true,  false],
            [true,  "foo", false, false, true],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldKnowWhenToUnderstandArray
     */
    public function shouldKnowWhenToUnderstandArray(bool $expectedResult, array $source, bool $a, bool $b, bool $c)
    {
        $this->innerArgumentFactoryA->expects($this->any())->method('understandsArray')->with(
            $this->equalTo($source)
        )->willReturn($a);
        $this->innerArgumentFactoryB->expects($this->any())->method('understandsArray')->with(
            $this->equalTo($source)
        )->willReturn($b);
        $this->innerArgumentFactoryC->expects($this->any())->method('understandsArray')->with(
            $this->equalTo($source)
        )->willReturn($c);

        /** @var bool $actualResult */
        $actualResult = $this->factoryAggregate->understandsArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldKnowWhenToUnderstandArray()
    {
        return array(
            [false, ["foo"], false, false, false],
            [true,  ["foo"], true,  false, false],
            [true,  ["foo"], false, true,  false],
            [true,  ["foo"], false, false, true],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldCreateArgumentFromString
     */
    public function shouldCreateArgumentFromString(
        $expectedResult,
        string $source,
        bool $au,
        ?Argument $ar,
        bool $bu,
        ?Argument $br,
        bool $cu,
        ?Argument $cr,
        bool $expectException
    ) {
        if ($expectException) {
            $this->expectException(InvalidArgumentException::class);
        }

        $this->innerArgumentFactoryA->expects($this->any())->method('understandsString')->with(
            $this->equalTo($source)
        )->willReturn($au);
        $this->innerArgumentFactoryB->expects($this->any())->method('understandsString')->with(
            $this->equalTo($source)
        )->willReturn($bu);
        $this->innerArgumentFactoryC->expects($this->any())->method('understandsString')->with(
            $this->equalTo($source)
        )->willReturn($cu);

        if ($au) {
            $this->innerArgumentFactoryA->expects($this->any())->method('createArgumentFromString')->with(
                $this->equalTo($source)
            )->willReturn($ar);
        }
        
        if ($bu) {
            $this->innerArgumentFactoryB->expects($this->any())->method('createArgumentFromString')->with(
                $this->equalTo($source)
            )->willReturn($br);
        }
        
        if ($cu) {
            $this->innerArgumentFactoryC->expects($this->any())->method('createArgumentFromString')->with(
                $this->equalTo($source)
            )->willReturn($cr);
        }

        /** @var mixed $actualResult */
        $actualResult = $this->factoryAggregate->createArgumentFromString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldCreateArgumentFromString()
    {
        return array(
            [new LiteralArgument(true), 'foo', true, new LiteralArgument(true), false, null, false, null, false],
            [new LiteralArgument(true), 'foo', false, null, true, new LiteralArgument(true), true, new LiteralArgument(false), false],
            [new LiteralArgument(true), 'foo', false, null, false, null, true, new LiteralArgument(true), false],
            [null, 'foo', false, null, false, null, false, null, true],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldCreateArgumentFromArray
     */
    public function shouldCreateArgumentFromArray(
        $expectedResult,
        array $source,
        bool $au,
        ?Argument $ar,
        bool $bu,
        ?Argument $br,
        bool $cu,
        ?Argument $cr,
        bool $expectException
    ) {
        if ($expectException) {
            $this->expectException(InvalidArgumentException::class);
        }

        $this->innerArgumentFactoryA->expects($this->any())->method('understandsArray')->with(
            $this->equalTo($source)
        )->willReturn($au);
        $this->innerArgumentFactoryB->expects($this->any())->method('understandsArray')->with(
            $this->equalTo($source)
        )->willReturn($bu);
        $this->innerArgumentFactoryC->expects($this->any())->method('understandsArray')->with(
            $this->equalTo($source)
        )->willReturn($cu);

        if ($au) {
            $this->innerArgumentFactoryA->expects($this->any())->method('createArgumentFromArray')->with(
                $this->equalTo($source)
            )->willReturn($ar);
        }
        
        if ($bu) {
            $this->innerArgumentFactoryB->expects($this->any())->method('createArgumentFromArray')->with(
                $this->equalTo($source)
            )->willReturn($br);
        }
        
        if ($cu) {
            $this->innerArgumentFactoryC->expects($this->any())->method('createArgumentFromArray')->with(
                $this->equalTo($source)
            )->willReturn($cr);
        }

        /** @var mixed $actualResult */
        $actualResult = $this->factoryAggregate->createArgumentFromArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldCreateArgumentFromArray()
    {
        return array(
            [new LiteralArgument(true), ['foo'], true, new LiteralArgument(true), false, null, false, null, false],
            [new LiteralArgument(true), ['foo'], false, null, true, new LiteralArgument(true), true, new LiteralArgument(false), false],
            [new LiteralArgument(true), ['foo'], false, null, false, null, true, new LiteralArgument(true), false],
            [null, ['foo'], false, null, false, null, false, null, true],
        );
    }

    /**
     * @test
     */
    public function shouldNotUnderstandAnythingWihtoutInnerFactories()
    {
        $this->assertFalse((new ArgumentFactoryAggregate([]))->understandsString(""));
        $this->assertFalse((new ArgumentFactoryAggregate([]))->understandsArray([]));
    }

    /**
     * @test
     */
    public function shouldRejectConstructorBeingCalledTwice()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factoryAggregate->__construct([]);
    }

}
