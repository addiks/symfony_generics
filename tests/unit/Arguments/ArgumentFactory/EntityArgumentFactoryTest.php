<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\Arguments\ArgumentFactory;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Arguments\ArgumentCall;
use Addiks\SymfonyGenerics\Arguments\Argument;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\EntityArgumentFactory;
use Addiks\SymfonyGenerics\Arguments\EntityArgument;
use Doctrine\Persistence\ObjectManager;

final class EntityArgumentFactoryTest extends TestCase
{

    private EntityArgumentFactory $factory;

    private ObjectManager $objectManager;

    private ArgumentFactory $argumentFactory;

    public function setUp()
    {
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->argumentFactory = $this->createMock(ArgumentFactory::class);

        $this->factory = new EntityArgumentFactory($this->objectManager, $this->argumentFactory);
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
            [true, 'Foo#bar'],
            [false, 'Foo#'],
            [false, '#bar'],
            [false, '#'],
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
            [true,  ['entity-class' => 'foo', 'entity-id' => 'bar']],
            [false, ['entity-id' => 'bar']],
            [false, ['entity-class' => 'foo']],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldCreateCallArgumentFromString
     */
    public function shouldCreateCallArgumentFromString(
        ?EntityArgument $expectedResult,
        string $source,
        bool $shouldRejectCreation
    ) {
        if ($shouldRejectCreation) {
            $this->expectException(InvalidArgumentException::class);

        } else {
            $this->argumentFactory->method('createArgumentFromString')->willReturn($this->createMock(Argument::class));
        }

        $actualResult = $this->factory->createArgumentFromString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldCreateCallArgumentFromString(): array
    {
        $this->setUp();

        return array(
            [new EntityArgument($this->objectManager, 'Foo', $this->createMock(Argument::class)), 'Foo#bar', false],
            [null, 'a#', true],
            [null, '#b', true],
            [null, '#', true],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldCreateCallArgumentFromArray
     */
    public function shouldCreateCallArgumentFromArray(
        ?EntityArgument $expectedResult,
        array $source,
        bool $shouldRejectCreation
    ) {
        if ($shouldRejectCreation) {
            $this->expectException(InvalidArgumentException::class);

        } else {
            $this->argumentFactory->method('createArgumentFromString')->willReturn($this->createMock(Argument::class));
            $this->argumentFactory->method('createArgumentFromArray')->willReturn($this->createMock(Argument::class));
        }

        $actualResult = $this->factory->createArgumentFromArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldCreateCallArgumentFromArray(): array
    {
        $this->setUp();

        return array(
            [null, [], true],
            [null, ['entity-id' => 'foo'], true],
            [null, ['entity-class' => 'bar'], true],
            [new EntityArgument($this->objectManager, 'Foo', $this->createMock(Argument::class)), [
                'entity-class' => 'Foo',
                'entity-id' => 'bar'
            ], false],
        );
    }

}
