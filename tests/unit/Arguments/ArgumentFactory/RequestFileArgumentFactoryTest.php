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
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\RequestFileArgumentFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Arguments\RequestFileArgument;

final class RequestFileArgumentFactoryTest extends TestCase
{

    /**
     * @var RequestFileArgumentFactory
     */
    private $factory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->factory = new RequestFileArgumentFactory($this->requestStack);
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldKnowIfUnderstandsString
     */
    public function shouldKnowIfUnderstandsString(bool $expectedResult, string $source)
    {
        /** @var bool $actualResult */
        $actualResult = $this->factory->understandsString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldKnowIfUnderstandsString()
    {
        return array(
            [true, '$files.foo'],
            [true, '$files.f'],
            [true, '$files.foo.bar'],
            [false, '$files.'],
            [false, '$file.foo'],
            [false, 'files.foo'],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldKnowIfUnderstandsArray
     */
    public function shouldKnowIfUnderstandsArray(bool $expectedResult, array $source)
    {
        /** @var bool $actualResult */
        $actualResult = $this->factory->understandsArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldKnowIfUnderstandsArray()
    {
        return array(
            [true, ['type' => 'request-file', 'key' => 'foo']],
            [true, ['type' => 'request-file', 'key' => 'bar']],
            [true, ['type' => 'request-file', 'key' => 'f']],
            [true, ['type' => 'request-file', 'key' => '']],
            [false, ['type' => 'request-file', ]],
            [false, ['key' => 'foo']],
            [false, ['type' => 'request-blah', 'key' => 'foo']],
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

        $actualResult = $this->factory->createArgumentFromString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldCreateArgumentFromString()
    {
        $this->setUp();

        return array(
            [new RequestFileArgument($this->requestStack, 'foo', 'content'), '$files.foo', false],
            [new RequestFileArgument($this->requestStack, 'foo', 'asd'), '$files.foo.asd', false],
            [new RequestFileArgument($this->requestStack, 'bar', 'asd'), '$files.bar.asd', false],
            [new RequestFileArgument($this->requestStack, '', 'content'), '$files.', false],
            [null, '$file.foo', true],
            [null, 'files.foo', true],
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

        $actualResult = $this->factory->createArgumentFromArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderForShouldCreateArgumentFromArray()
    {
        $this->setUp();

        return array(
            [new RequestFileArgument($this->requestStack, 'foo', 'content'), ['key' => 'foo'], false],
            [new RequestFileArgument($this->requestStack, 'foo', 'asd'), ['key' => 'foo', 'property' => 'asd'], false],
            [new RequestFileArgument($this->requestStack, 'bar', 'asd'), ['key' => 'bar', 'property' => 'asd'], false],
            [new RequestFileArgument($this->requestStack, '', 'content'), ['key' => ''], false],
            [null, [], true],
            [null, ['property' => 'asd'], true],
        );
    }

}
