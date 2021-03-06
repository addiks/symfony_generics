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
use Addiks\SymfonyGenerics\Arguments\ArgumentCall;
use Addiks\SymfonyGenerics\Arguments\Argument;
use InvalidArgumentException;
use stdClass;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use ReflectionMethod;

final class ArgumentCallTest extends TestCase
{

    /**
     * @test
     */
    public function shouldResolveCallArgument()
    {
        /** @var Argument $callee */
        $callee = $this->createMock(Argument::class);
        $callee->method("resolve")->willReturn($this);

        /** @var Argument $argumentA */
        $argumentA = $this->createMock(Argument::class);
        $argumentA->method("resolve")->willReturn("some-foo");

        /** @var Argument $argumentB */
        $argumentB = $this->createMock(Argument::class);
        $argumentB->method("resolve")->willReturn(31415);

        /** @var ArgumentCompilerInterface $argumentCompiler */
        $argumentCompiler = $this->createMock(ArgumentCompilerInterface::class);
        $argumentCompiler->expects($this->any())->method('buildCallArguments')->with(
            $this->equalTo(new ReflectionMethod(__CLASS__, 'someMethod')),
            $this->equalTo(["some-foo", 31415])
        )->willReturn(["some-foo", 31415]);

        $subject = new ArgumentCall(
            $argumentCompiler,
            $callee,
            "someMethod",
            [
                $argumentA,
                $argumentB
            ]
        );

        /** @var mixed $actualResult */
        $actualResult = $subject->resolve();

        $this->assertEquals("some-result", $actualResult);
    }

    public function someMethod(string $foo, int $bar): string
    {
        $this->assertEquals("some-foo", $foo);
        $this->assertEquals(31415, $bar);
        return "some-result";
    }

    /**
     * @test
     */
    public function shouldRejectNonObjectCallee()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var Argument $callee */
        $callee = $this->createMock(Argument::class);
        $callee->method("resolve")->willReturn("non-object");

        $subject = new ArgumentCall(
            $this->createMock(ArgumentCompilerInterface::class),
            $callee,
            "someMethod",
            []
        );

        $subject->resolve();
    }

}
