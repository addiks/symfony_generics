<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Controllers\GenericTemplateRenderController;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;

final class GenericTemplateRenderControllerTest extends TestCase
{

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentCompiler;

    public function setUp()
    {
        $this->controllerHelper = $this->createMock(ControllerHelperInterface::class);
        $this->argumentCompiler = $this->createMock(ArgumentCompilerInterface::class);
    }

    /**
     * @test
     */
    public function shouldRenderTemplate()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Response $expectedResponse */
        $expectedResponse = $this->createMock(Response::class);

        $this->argumentCompiler->expects($this->once())->method('buildArguments')->with(
            $this->equalTo(['foo' => 'bar']),
            $this->identicalTo($request)
        )->willReturn([
            'bar' => 'baz'
        ]);

        $this->controllerHelper->expects($this->once())->method('renderTemplate')->with(
            $this->equalTo("@foo/bar/baz.html"),
            $this->equalTo(['bar' => 'baz'])
        )->willReturn($expectedResponse);

        $controller = new GenericTemplateRenderController($this->controllerHelper, $this->argumentCompiler, [
            'template' => "@foo/bar/baz.html",
            'arguments' => [
                'foo' => 'bar'
            ]
        ]);

        /** @var Response $actualResponse */
        $actualResponse = $controller->renderTemplate($request);

        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @test
     */
    public function shouldCheckIfAccessIsGranted()
    {
        $this->expectException(AccessDeniedException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->controllerHelper->expects($this->once())->method('denyAccessUnlessGranted')->with(
            $this->equalTo('some-attribute'),
            $this->equalTo($request)
        )->will($this->returnCallback(
            function () {
                throw new AccessDeniedException('Lorem ipsum!');
            }
        ));

        /** @var mixed $controller */
        $controller = new GenericTemplateRenderController($this->controllerHelper, $this->argumentCompiler, [
            'template' => "@foo/bar/baz.html",
            'authorization-attribute' => 'some-attribute',
        ]);

        $controller->renderTemplate($request);
    }

    /**
     * @test
     */
    public function shouldRejectConstructorCalledAgain()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericTemplateRenderController($this->controllerHelper, $this->argumentCompiler, [
            'template' => "@foo/bar/baz.html",
        ]);

        $controller->__construct($this->controllerHelper, $this->argumentCompiler, [
            'template' => "@foo/bar/baz.html",
        ]);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfTemplatePathIsMissing()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericTemplateRenderController($this->controllerHelper, $this->argumentCompiler, [
        ]);
    }

    /**
     * @test
     */
    public function shouldBeCallableByInvokingController()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Response $expectedResponse */
        $expectedResponse = $this->createMock(Response::class);

        $this->argumentCompiler->expects($this->once())->method('buildArguments')->with(
            $this->equalTo(['foo' => 'bar']),
            $this->identicalTo($request)
        )->willReturn([
            'bar' => 'baz'
        ]);

        $this->controllerHelper->expects($this->once())->method('renderTemplate')->with(
            $this->equalTo("@foo/bar/baz.html"),
            $this->equalTo(['bar' => 'baz'])
        )->willReturn($expectedResponse);

        $controller = new GenericTemplateRenderController($this->controllerHelper, $this->argumentCompiler, [
            'template' => "@foo/bar/baz.html",
            'arguments' => [
                'foo' => 'bar'
            ]
        ]);

        $this->controllerHelper->method('getCurrentRequest')->willReturn($request);

        /** @var Response $actualResponse */
        $actualResponse = $controller();

        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @test
     */
    public function shouldRejectCallWithoutRequest()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericTemplateRenderController($this->controllerHelper, $this->argumentCompiler, [
            'template' => "@foo/bar/baz.html",
            'arguments' => []
        ]);

        $this->controllerHelper->method('getCurrentRequest')->willReturn(null);

        $controller();
    }

}
