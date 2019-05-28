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
use Addiks\SymfonyGenerics\Arguments\ArgumentContext;
use stdClass;
use InvalidArgumentException;

final class ArgumentContextTest extends TestCase
{

    /**
     * @test
     */
    public function shouldContainContextualData()
    {
        $context = new ArgumentContext();

        $this->assertFalse($context->has("foo"));
        $this->assertFalse($context->has("bar"));
        $this->assertFalse($context->has("baz"));

        $context->set("foo", 1234);

        $this->assertTrue($context->has("foo"));
        $this->assertFalse($context->has("bar"));
        $this->assertFalse($context->has("baz"));
        $this->assertEquals(1234, $context->get("foo"));

        $context->set("bar", ['lorem' => 'ipsum', 'dolor' => 'amet']);

        $this->assertTrue($context->has("foo"));
        $this->assertTrue($context->has("bar"));
        $this->assertFalse($context->has("baz"));
        $this->assertEquals(1234, $context->get("foo"));
        $this->assertEquals(['lorem' => 'ipsum', 'dolor' => 'amet'], $context->get("bar"));

        $context->set("baz", new stdClass());

        $this->assertTrue($context->has("foo"));
        $this->assertTrue($context->has("bar"));
        $this->assertTrue($context->has("baz"));
        $this->assertEquals(1234, $context->get("foo"));
        $this->assertEquals(['lorem' => 'ipsum', 'dolor' => 'amet'], $context->get("bar"));
        $this->assertEquals(new stdClass(), $context->get("baz"));

        $context->clear();

        $this->assertFalse($context->has("foo"));
        $this->assertFalse($context->has("bar"));
        $this->assertFalse($context->has("baz"));
    }

    /**
     * @test
     */
    public function shouldRejectGettingUnknownVariable()
    {
        $this->expectException(InvalidArgumentException::class);

        (new ArgumentContext())->get("foo");
    }

}
