<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\Arguments;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Arguments\LiteralArgument;

final class LiteralArgumentTest extends TestCase
{

    /**
     * @test
     */
    public function shouldResolveLiteral()
    {
        $argument = new LiteralArgument("some-literal");
        $this->assertEquals("some-literal", $argument->resolve());
    }

    /**
     * @test
     */
    public function shouldResolveTrueLiteral()
    {
        $argument = new LiteralArgument("true");
        $this->assertEquals(true, $argument->resolve());
    }

    /**
     * @test
     */
    public function shouldResolveFalseLiteral()
    {
        $argument = new LiteralArgument("false");
        $this->assertEquals(false, $argument->resolve());
    }

    /**
     * @test
     */
    public function shouldResolveNullLiteral()
    {
        $argument = new LiteralArgument("null");
        $this->assertEquals(null, $argument->resolve());
    }

}
