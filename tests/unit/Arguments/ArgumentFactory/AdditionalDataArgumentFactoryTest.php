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
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\AdditionalDataArgumentFactory;
use Addiks\SymfonyGenerics\Arguments\ArgumentContextInterface;
use InvalidArgumentException;
use Addiks\SymfonyGenerics\Arguments\AdditionalDataArgument;

final class AdditionalDataArgumentFactoryTest extends TestCase
{

    /**
     * @var AdditionalDataArgumentFactory
     */
    private $factory;

    /**
     * @var ArgumentContextInterface
     */
    private $context;

    public function setUp()
    {
        $this->context = $this->createMock(ArgumentContextInterface::class);

        $this->factory = new AdditionalDataArgumentFactory($this->context);
    }

    /**
     * @test
     * @dataProvider dataproviderForShouldKnowWhatStringsToUnderstand
     */
    public function shouldKnowWhatStringsToUnderstand(bool $expectedResult, string $source)
    {
        /** @var bool $actualResult */
        $actualResult = $this->factory->understandsString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataproviderForShouldKnowWhatStringsToUnderstand(): array
    {
        return array(
            [true,  '%foo%'],
            [true,  '%bar%'],
            [true,  '%baz%'],
            [false, 'foo'],
            [false, '%foo'],
            [false, 'foo%'],
            [false, '$foo'],
            [false, ''],
        );
    }

    /**
     * @test
     * @dataProvider dataproviderForShouldKnowWhatArraysToUnderstand
     */
    public function shouldKnowWhatArraysToUnderstand(bool $expectedResult, array $source)
    {
        /** @var bool $actualResult */
        $actualResult = $this->factory->understandsArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataproviderForShouldKnowWhatArraysToUnderstand(): array
    {
        return array(
            [true,  ['type' => 'additional-data', 'key' => 'foo']],
            [true,  ['type' => 'additional-data', 'key' => 'bar']],
            [true,  ['type' => 'additional-data', 'key' => 'a']],
            [true,  ['type' => 'additional-data', 'key' => 'aaaaaaaaaaaaaaaaaa']],
            [false, ['type' => 'additional-data', 'key' => null]],
            [false, ['type' => 'additional-NULL', 'key' => 'foo']],
            [false, ['type' => 'additional-data', 'ley' => 'foo']],
            [false, ['type' => 'additional-data']],
            [false, ['key' => 'foo']],
        );
    }

    /**
     * @test
     * @dataProvider dataproviderForShouldCreateArgumentFromString
     */
    public function shouldCreateArgumentFromString($expectedResult, string $source, bool $shouldReject)
    {
        if ($shouldReject) {
            $this->expectException(InvalidArgumentException::class);
        }

        /** @var object $actualResult */
        $actualResult = $this->factory->createArgumentFromString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataproviderForShouldCreateArgumentFromString(): array
    {
        $this->setUp();

        return array(
            [new AdditionalDataArgument('foo', $this->context), '%foo%', false],
            [new AdditionalDataArgument('lorem-ipsum', $this->context), '%lorem-ipsum%', false],
            [new AdditionalDataArgument('a', $this->context), '%a%', false],
            [null, '%%', true],
            [null, 'foo%', true],
            [null, '%foo', true],
            [null, 'foo', true],
        );
    }

    /**
     * @test
     * @dataProvider dataproviderForShouldCreateArgumentFromArray
     */
    public function shouldCreateArgumentFromArray($expectedResult, array $source, bool $shouldReject)
    {
        if ($shouldReject) {
            $this->expectException(InvalidArgumentException::class);
        }

        /** @var object $actualResult */
        $actualResult = $this->factory->createArgumentFromArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataproviderForShouldCreateArgumentFromArray(): array
    {
        $this->setUp();

        return array(
            [new AdditionalDataArgument('foo', $this->context), ['type' => 'additional-data', 'key' => 'foo'], false],
            [new AdditionalDataArgument('lorem-ipsum', $this->context), ['type' => 'additional-data', 'key' => 'lorem-ipsum'], false],
            [new AdditionalDataArgument('a', $this->context), ['type' => 'additional-data', 'key' => 'a'], false],
            [null, ['type' => 'additional-data', 'key' => null], true],
            [null, ['type' => 'additional-data', ], true],
            [null, ['key' => null], true],
        );
    }

}
