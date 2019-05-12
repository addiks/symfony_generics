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

    public function setUp()
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

}
