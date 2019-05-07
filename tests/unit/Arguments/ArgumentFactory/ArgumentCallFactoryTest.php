<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentCallFactory;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;

final class ArgumentCallFactoryTest extends TestCase
{

    /**
     * @var ArgumentCallFactory
     */
    private $factory;

    /**
     * @var ArgumentFactory
     */
    private $argumentFactory;

    public function setUp()
    {
        $this->argumentFactory = $this->createMock(ArgumentFactory::class);

        $this->factory = new ArgumentCallFactory($this->argumentFactory);
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

}
