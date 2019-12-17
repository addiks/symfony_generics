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
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentCallFactory;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Arguments\ArgumentCall;
use Addiks\SymfonyGenerics\Arguments\Argument;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;

final class ArgumentCallFactoryTest extends TestCase
{

    /**
     * @var ArgumentCallFactory
     */
    private $factory;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentCompiler;

    /**
     * @var ArgumentFactory
     */
    private $argumentFactory;

    public function setUp()
    {
        $this->argumentCompiler = $this->createMock(ArgumentCompilerInterface::class);
        $this->argumentFactory = $this->createMock(ArgumentFactory::class);

        $this->factory = new ArgumentCallFactory($this->argumentCompiler, $this->argumentFactory);
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
            [true, 'a::b'],
            [true, 'foo::bar'],
            [true, 'foo::bar(baz)'],
            [false, '::b'],
            [false, 'a::'],
            [false, '::'],
            [false, ''],
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
            [true,  ['callee' => 'foo', 'method' => 'bar']],
            [true,  ['callee' => 'foo', 'method' => 'bar', 'arguments' => []]],
            [false, ['method' => 'bar']],
            [false, ['callee' => 'foo']],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldCreateCallArgumentFromString
     */
    public function shouldCreateCallArgumentFromString(
        ?ArgumentCall $expectedResult,
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
        return array(
            [
                new ArgumentCall(
                    $this->createMock(ArgumentCompilerInterface::class),
                    $this->createMock(Argument::class),
                    'someMethod',
                    []
                ),
                'some-callee::someMethod',
                false
            ],
            [
                new ArgumentCall(
                    $this->createMock(ArgumentCompilerInterface::class),
                    $this->createMock(Argument::class),
                    'someMethod',
                    [
                        $this->createMock(Argument::class),
                        $this->createMock(Argument::class)
                    ]
                ),
                'some-callee::someMethod(a, b)',
                false
            ],
            [null, 'a::', true],
            [null, '::b', true],
            [null, '::', true],
        );
    }

    /**
     * @test
     */
    public function shouldCreateArgumentsFromArgumentsInString()
    {
        $this->argumentFactory->expects($this->exactly(3))->method('createArgumentFromString')->withConsecutive(
            [$this->equalTo('a')],
            [$this->equalTo('b')],
            [$this->equalTo('some-callee')]
        )->willReturn($this->createMock(Argument::class));
        $this->factory->createArgumentFromString('some-callee::someMethod(a, b)');
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldCreateCallArgumentFromArray
     */
    public function shouldCreateCallArgumentFromArray(
        $expectedResult,
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
        return array(
            [null, [], true],
            [null, ['method' => 'foo'], true],
            [null, ['callee' => 'bar'], true],
            [new ArgumentCall(
                $this->createMock(ArgumentCompilerInterface::class),
                $this->createMock(Argument::class),
                'someMethod',
                []
            ), [
                'callee' => 'some-callee',
                'method' => 'someMethod'
            ], false],
            [new ArgumentCall(
                $this->createMock(ArgumentCompilerInterface::class),
                $this->createMock(Argument::class),
                'someMethod',
                []
            ), [
                'callee' => ['some-callee'],
                'method' => 'someMethod'
            ], false],
            [new ArgumentCall(
                $this->createMock(ArgumentCompilerInterface::class),
                $this->createMock(Argument::class),
                'someMethod',
                [
                    $this->createMock(Argument::class),
                    $this->createMock(Argument::class)
                ]
            ), [
                'callee' => 'some-callee',
                'method' => 'someMethod',
                'arguments' => [
                    'foo',
                    'bar'
                ]
            ], false],
            [new ArgumentCall(
                $this->createMock(ArgumentCompilerInterface::class),
                $this->createMock(Argument::class),
                'someMethod', [
                    $this->createMock(Argument::class),
                    $this->createMock(Argument::class)
                ]
            ), [
                'callee' => ['some-callee'],
                'method' => 'someMethod',
                'arguments' => [
                    'foo',
                    'bar'
                ]
            ], false],
            [new ArgumentCall(
                $this->createMock(ArgumentCompilerInterface::class),
                $this->createMock(Argument::class),
                'someMethod', [
                    $this->createMock(Argument::class),
                    $this->createMock(Argument::class)
                ]
            ), [
                'callee' => 'some-callee',
                'method' => 'someMethod',
                'arguments' => [
                    ['foo'],
                    ['bar']
                ]
            ], false],
        );
    }

}
