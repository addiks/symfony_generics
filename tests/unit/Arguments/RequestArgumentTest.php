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
use Addiks\SymfonyGenerics\Arguments\RequestArgument;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;

final class RequestArgumentTest extends TestCase
{

    /**
     * @test
     */
    public function shouldResolveRequestArgument()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('get')->with(
            $this->equalTo("some-request-key")
        )->willReturn("some-request-data");

        /** @var RequestStack $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $subject = new RequestArgument($requestStack, "some-request-key");

        $this->assertEquals("some-request-data", $subject->resolve());
    }

    /**
     * @test
     */
    public function shouldRejectResolvingWithoutRequest()
    {
        $this->expectException(InvalidArgumentException::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        $argument = new RequestArgument($requestStack, "some-request-key");
        $argument->resolve();
    }

}
