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
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\LiteralArgumentFactory;
use Addiks\SymfonyGenerics\Arguments\LiteralArgument;

final class LiteralArgumentFactoryTest extends TestCase
{

    /**
     * @test
     */
    public function understandsAllTheThings()
    {
        $factory = new LiteralArgumentFactory();

        $this->assertTrue($factory->understandsString("foo"));
        $this->assertTrue($factory->understandsArray([]));
    }

    /**
     * @test
     */
    public function shouldCreateArgumentFromString()
    {
        $factory = new LiteralArgumentFactory();

        $this->assertEquals(new LiteralArgument('foo'), $factory->createArgumentFromString("foo"));
        $this->assertEquals(new LiteralArgument('bar'), $factory->createArgumentFromString("bar"));
        $this->assertEquals(new LiteralArgument('foo'), $factory->createArgumentFromString('"foo"'));
        $this->assertEquals(new LiteralArgument('bar'), $factory->createArgumentFromString("'bar'"));
    }

    /**
     * @test
     */
    public function shouldCreateArgumentFromArray()
    {
        $this->assertEquals(
            new LiteralArgument(['foo']),
            (new LiteralArgumentFactory())->createArgumentFromArray(["foo"])
        );

    }

}
