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
use Addiks\SymfonyGenerics\Controllers\GenericExceptionResponseController;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Serializable;
use Symfony\Component\HttpFoundation\Response;
use InvalidArgumentException;
use DivisionByZeroError;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

final class GenericExceptionResponseControllerTest extends TestCase
{

    /**
     * @var GenericExceptionResponseController
     */
    private $controller;

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var Serializable
     */
    private $innerController;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentBuilder;

    public function setUp()
    {
        $this->controllerHelper = $this->createMock(ControllerHelperInterface::class);
        $this->innerController = $this->createMock(Serializable::class);
        $this->argumentBuilder = $this->createMock(ArgumentCompilerInterface::class);
    }

    /**
     * @test
     */
    public function shouldexecuteInnerControllerSafely()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);

        $this->innerController->method("serialize")->willReturn(new Response("foo", 205));

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely($request);

        $this->assertEquals("foo", $response->getContent());
        $this->assertEquals(205, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldOverrideSuccessResponse()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'success-response' => "Some success!",
            'success-response-code' => 234,
        ]);

        $this->innerController->method("serialize")->willReturn(new Response("foo", 205));

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely($request);

        $this->assertEquals("Some success!", $response->getContent());
        $this->assertEquals(234, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldOverrideSuccessResponseWithDefaultResponseCode()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'success-response' => "Some success!",
        ]);

        $this->innerController->method("serialize")->willReturn(new Response("foo", 205));

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely($request);

        $this->assertEquals("Some success!", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldTriggerSuccessFlashMessage()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'success-flash-message' => 'Some Success Message!'
        ]);

        $this->controllerHelper->expects($this->once())->method('addFlashMessage')->with(
            $this->equalTo('Some Success Message!'),
            $this->equalTo('success')
        );

        $this->innerController->method("serialize")->willReturn(new Response("foo", 200));

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $controller->executeInnerControllerSafely($request);
    }

    /**
     * @test
     */
    public function shouldAddFlashMessageUponException()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'exception-responses' => [
                InvalidArgumentException::class => [
                    'flash-type' => 'danger',
                    'flash-message' => 'Something happened: %s'
                ]
            ]
        ]);

        $this->innerController->method("serialize")->will($this->returnCallback(
            function () {
                throw new InvalidArgumentException("Lorem ipsum!");
            }
        ));

        $this->controllerHelper->expects($this->once())->method('handleException');
        $this->controllerHelper->expects($this->once())->method('addFlashMessage')->with(
            $this->equalTo('Something happened: Lorem ipsum!'),
            $this->equalTo('danger')
        );

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely($request);

        $this->assertEquals("Lorem ipsum!", $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldForwardThrowExceptionIfNotHandled()
    {
        /** @var mixed $controller */
        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);

        $this->innerController->method("serialize")->will($this->returnCallback(
            function () {
                throw new InvalidArgumentException("Lorem ipsum!");
            }
        ));

        $this->expectException(InvalidArgumentException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $controller->executeInnerControllerSafely($request);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfInnerControllerDidNotReturnAResponse()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);

        $this->expectException(InvalidArgumentException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $controller->executeInnerControllerSafely($request);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfHandledExceptionClassIsNotException()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'exception-responses' => [
                "TotallyNotAnException" => [
                    'flash-type' => 'danger',
                    'flash-message' => 'Something happened: %s'
                ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function shouldNotHandleThrowables()
    {
        $this->expectException(DivisionByZeroError::class);

        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'exception-responses' => [
                DivisionByZeroError::class => [
                    'flash-type' => 'danger',
                    'flash-message' => 'Something happened: %s'
                ]
            ]
        ]);

        $this->innerController->method("serialize")->will($this->returnCallback(
            function () {
                throw new DivisionByZeroError("Lorem ipsum");
            }
        ));

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely($request);

        $this->assertEquals("Lorem ipsum", $response->getContent());
    }

    /**
     * @test
     */
    public function shouldRejectNonObjectInnerController()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => false,
            'inner-controller-method' => "serialize",
        ]);
    }

    /**
     * @test
     */
    public function shouldRejectControllerBeingCalledAgain()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);

        $controller->__construct($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);
    }

    /**
     * @test
     */
    public function shouldRejectNonArrayGivenAsInnerControllerArguments()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'arguments' => "Lorem ipsum",
        ]);
    }

    /**
     * @test
     */
    public function shouldRedirectFromException()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'exception-responses' => [
                InvalidArgumentException::class => [
                    'redirect-route' => 'some_redirect_route',
                    'redirect-route-parameters' => [
                        'foo' => 'bar'
                    ]
                ]
            ]
        ]);

        $this->innerController->method("serialize")->will($this->returnCallback(
            function () {
                throw new InvalidArgumentException("Lorem ipsum!");
            }
        ));

        /** @var RedirectResponse $redirectResponse */
        $redirectResponse = $this->createMock(RedirectResponse::class);

        $this->controllerHelper->expects($this->once())->method('handleException');
        $this->controllerHelper->expects($this->once())->method('redirectToRoute')->with(
            $this->equalTo('some_redirect_route'),
            $this->equalTo([]),
            $this->equalTo(301)
        )->willReturn($redirectResponse);

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes->method('get')->willReturn([]);

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely($request);

        $this->assertSame($redirectResponse, $response);
    }

    /**
     * @test
     */
    public function shouldBeCallableByInvokingController()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'exception-responses' => [
                InvalidArgumentException::class => [
                    'redirect-route' => 'some_redirect_route',
                    'redirect-route-parameters' => [
                        'foo' => 'bar'
                    ]
                ]
            ]
        ]);

        $this->innerController->method("serialize")->will($this->returnCallback(
            function () {
                throw new InvalidArgumentException("Lorem ipsum!");
            }
        ));

        /** @var RedirectResponse $redirectResponse */
        $redirectResponse = $this->createMock(RedirectResponse::class);

        $this->controllerHelper->expects($this->once())->method('handleException');
        $this->controllerHelper->expects($this->once())->method('redirectToRoute')->with(
            $this->equalTo('some_redirect_route'),
            $this->equalTo([]),
            $this->equalTo(301)
        )->willReturn($redirectResponse);

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes->method('get')->willReturn([]);

        $this->controllerHelper->method('getCurrentRequest')->willReturn($request);

        /** @var Response $response */
        $response = $controller();

        $this->assertSame($redirectResponse, $response);
    }

    /**
     * @test
     */
    public function shouldRejectCallWithoutRequest()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericExceptionResponseController($this->controllerHelper, $this->argumentBuilder, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);

        $this->controllerHelper->method('getCurrentRequest')->willReturn(null);

        $controller();
    }

}
