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
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\RequestArgumentFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Arguments\RequestArgument;

final class RequestArgumentFactoryTest extends TestCase
{

    /**
     * @var RequestArgumentFactory
     */
    private $factory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->factory = new RequestArgumentFactory($this->requestStack);
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldKnowIfUnderstandAString
     */
    public function shouldKnowIfUnderstandAString(bool $expectedResult, string $source)
    {
        /** @var bool $actualResult */
        $actualResult = $this->factory->understandsString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldKnowIfUnderstandAString()
    {
        return array(
            [true, "\$foo"],
            [true, "\$f"],
            [false, "\$ "],
            [false, "\$-"],
            [false, "\$"],
            [false, "foo"],
            [false, "f"],
            [false, ""],
            [false, "-"],
            [false, "_"],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldKnowIfUnderstandAnArray
     */
    public function shouldKnowIfUnderstandAnArray(bool $expectedResult, array $source)
    {
        /** @var bool $actualResult */
        $actualResult = $this->factory->understandsArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldKnowIfUnderstandAnArray()
    {
        return array(
            [true, ['key' => 'foo', 'type' => 'request']],
            [true, ['key' => 'bar', 'type' => 'request']],
            [true, ['key' => 'a', 'type' => 'request']],
            [true, ['key' => '', 'type' => 'request']],
            [false, ['key' => '', 'type' => 'foo']],
            [false, ['key' => '']],
            [false, ['type' => 'request']],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldCreateRequestArgumentFromString
     */
    public function shouldCreateRequestArgumentFromString($expectedResult, string $source, bool $shouldRejectCreation)
    {
        if ($shouldRejectCreation) {
            $this->expectException(InvalidArgumentException::class);
        }

        /** @var mixed $actualResult */
        $actualResult = $this->factory->createArgumentFromString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldCreateRequestArgumentFromString()
    {
        $this->setUp();

        return array(
            [new RequestArgument($this->requestStack, "someKey"), '$someKey', false],
            [new RequestArgument($this->requestStack, "a"), '$a', false],
            [null, '$', true],
            [null, 'a', true],
            [null, '', true],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldCreateRequestArgumentFromArray
     */
    public function shouldCreateRequestArgumentFromArray($expectedResult, array $source, bool $shouldRejectCreation)
    {
        if ($shouldRejectCreation) {
            $this->expectException(InvalidArgumentException::class);
        }

        /** @var mixed $actualResult */
        $actualResult = $this->factory->createArgumentFromArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldCreateRequestArgumentFromArray()
    {
        $this->setUp();

        return array(
            [new RequestArgument($this->requestStack, "someKey"), ['key' => 'someKey'], false],
            [new RequestArgument($this->requestStack, "a"), ['key' => 'a'], false],
            [null, [], true],
        );
    }

}
