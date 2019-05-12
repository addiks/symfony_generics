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
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\RequestPayloadArgumentFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Addiks\SymfonyGenerics\Arguments\RequestPayloadArgument;

final class RequestPayloadArgumentFactoryTest extends TestCase
{

    /**
     * @var RequestPayloadArgumentFactory
     */
    private $factory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->factory = new RequestPayloadArgumentFactory($this->requestStack);
    }

    /**
     * @test
     */
    public function shouldKnowIfUnderstandString()
    {
        $this->assertEquals(true, $this->factory->understandsString("$"));
        $this->assertEquals(false, $this->factory->understandsString("not $"));
    }

    /**
     * @test
     */
    public function shouldKnowIfUnderstandArray()
    {
        $this->assertEquals(true, $this->factory->understandsArray(['type' => 'request-payload']));
        $this->assertEquals(false, $this->factory->understandsArray(['type' => 'anything else']));
    }

    /**
     * @test
     */
    public function shouldCreateArguments()
    {
        $this->assertEquals(
            new RequestPayloadArgument($this->requestStack),
            $this->factory->createArgumentFromString("")
        );
        $this->assertEquals(
            new RequestPayloadArgument($this->requestStack),
            $this->factory->createArgumentFromArray([])
        );
    }

}
