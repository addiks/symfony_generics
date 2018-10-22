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

    public function setUp()
    {
        $this->controllerHelper = $this->createMock(ControllerHelperInterface::class);
        $this->innerController = $this->createMock(Serializable::class);
    }

    /**
     * @test
     */
    public function shouldExecuteInnerControllerSafely()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);

        $this->innerController->method("serialize")->willReturn(new Response("foo", 205));

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely();

        $this->assertEquals("foo", $response->getContent());
        $this->assertEquals(205, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldOverrideSuccessResponse()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'success-response' => "Some success!",
            'success-response-code' => 234,
        ]);

        $this->innerController->method("serialize")->willReturn(new Response("foo", 205));

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely();

        $this->assertEquals("Some success!", $response->getContent());
        $this->assertEquals(234, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldOverrideSuccessResponseWithDefaultResponseCode()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'success-response' => "Some success!",
        ]);

        $this->innerController->method("serialize")->willReturn(new Response("foo", 205));

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely();

        $this->assertEquals("Some success!", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldTriggerSuccessFlashMessage()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
            'success-flash-message' => 'Some Success Message!'
        ]);

        $this->controllerHelper->expects($this->once())->method('addFlashMessage')->with(
            $this->equalTo('Some Success Message!'),
            $this->equalTo('success')
        );

        $this->innerController->method("serialize")->willReturn(new Response("foo", 200));

        $controller->executeInnerControllerSafely();
    }

    /**
     * @test
     */
    public function shouldAddFlashMessageUponException()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, [
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

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely();

        $this->assertEquals("Lorem ipsum!", $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldForwardThrowExceptionIfNotHandled()
    {
        /** @var mixed $controller */
        $controller = new GenericExceptionResponseController($this->controllerHelper, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);

        $this->innerController->method("serialize")->will($this->returnCallback(
            function () {
                throw new InvalidArgumentException("Lorem ipsum!");
            }
        ));

        $this->expectException(InvalidArgumentException::class);

        $controller->executeInnerControllerSafely();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfInnerControllerDidNotReturnAResponse()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);

        $this->expectException(InvalidArgumentException::class);

        $controller->executeInnerControllerSafely();
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfHandledExceptionClassIsNotException()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericExceptionResponseController($this->controllerHelper, [
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
    public function shouldHandleThrowables()
    {
        $controller = new GenericExceptionResponseController($this->controllerHelper, [
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

        /** @var Response $response */
        $response = $controller->executeInnerControllerSafely();

        $this->assertEquals("Lorem ipsum", $response->getContent());
    }

    /**
     * @test
     */
    public function shouldRejectNonObjectInnerController()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericExceptionResponseController($this->controllerHelper, [
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

        $controller = new GenericExceptionResponseController($this->controllerHelper, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);

        $controller->__construct($this->controllerHelper, [
            'inner-controller' => $this->innerController,
            'inner-controller-method' => "serialize",
        ]);
    }

}
