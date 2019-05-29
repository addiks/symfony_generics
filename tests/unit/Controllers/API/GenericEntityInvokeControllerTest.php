<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\Controllers\API;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Controllers\API\GenericEntityInvokeController;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Symfony\Component\HttpFoundation\Request;
use ReflectionMethod;
use InvalidArgumentException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Addiks\SymfonyGenerics\Events\EntityInteractionEvent;
use Symfony\Component\HttpFoundation\Response;
use Addiks\SymfonyGenerics\Tests\Unit\Controllers\SampleEntity;

final class GenericEntityInvokeControllerTest extends TestCase
{

    /**
     * @var GenericEntityInvokeController
     */
    private $controller;

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
    public function shouldInvodeAnEntityMethod()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->controller = new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'entity-class' => get_class($this->argumentCompiler),
                'method' => 'buildArguments',
                'arguments' => [
                    'argumentsConfiguration' => "Lorem",
                    'request' => "ipsum"
                ]
            ]
        );

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(get_class($this->argumentCompiler)),
            $this->equalTo("123")
        )->willReturn($this->argumentCompiler);

        $this->controllerHelper->expects($this->once())->method('flushORM');
        $this->controllerHelper->expects($this->once())->method('dispatchEvent')->with(
            $this->equalTo("symfony_generics.entity_interaction"),
            $this->equalTo(new EntityInteractionEvent(
                get_class($this->argumentCompiler),
                "123",
                $this->argumentCompiler,
                'buildArguments',
                [['foo' => 'bar'], $request]
            ))
        );

        $this->argumentCompiler->expects($this->once())->method('buildCallArguments')->with(
            $this->equalTo(new ReflectionMethod(get_class($this->argumentCompiler), 'buildArguments')),
            $this->equalTo([
                'argumentsConfiguration' => "Lorem",
                'request' => "ipsum"
            ]),
            $this->identicalTo($request)
        )->willReturn([
            ['foo' => 'bar'],
            $request
        ]);

        $this->argumentCompiler->expects($this->once())->method('buildArguments')->with(
            $this->equalTo(['foo' => 'bar']),
            $this->identicalTo($request)
        );

        $this->controller->invokeEntityMethod($request, "123");
    }

    /**
     * @test
     */
    public function shouldRedirectAfterInvokation()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->controller = new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'entity-class' => SampleEntity::class,
                'method' => 'getId',
                'redirect-route' => 'some-redirect-route',
                'redirect-route-parameters' => ['some-redirect-route-parameters']
            ]
        );

        /** @var SampleEntity $entity */
        $entity = $this->createMock(SampleEntity::class);
        $entity->method('getId')->willReturn("some-result");

        $this->argumentCompiler->expects($this->once())->method('buildArguments')->with(
            $this->equalTo(['some-redirect-route-parameters']),
            $this->identicalTo($request),
            $this->equalTo(['result' => "some-result"])
        )->willReturn(['foo' => 'bar']);

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo("123")
        )->willReturn($entity);

        $this->controllerHelper->expects($this->once())->method('redirectToRoute')->with(
            $this->equalTo('some-redirect-route'),
            $this->equalTo(['foo' => 'bar'])
        )->willReturn($this->createMock(Response::class));

        $this->controller->invokeEntityMethod($request, "123");
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenEntityNotFound()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->controller = new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'entity-class' => get_class($this->argumentCompiler),
                'method' => 'buildArguments',
            ]
        );

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(get_class($this->argumentCompiler)),
            $this->equalTo("123")
        )->willReturn(null);

        $this->controller->invokeEntityMethod($request, "123");
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenAccessNotGranted()
    {
        $this->expectException(AccessDeniedException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->controller = new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'entity-class' => get_class($this->argumentCompiler),
                'method' => 'buildArguments',
                'deny-access-attribute' => 'foo',
            ]
        );

        $this->controllerHelper->expects($this->once())->method('denyAccessUnlessGranted')->with(
            $this->equalTo('foo'),
            $this->equalTo($this->argumentCompiler)
        )->will($this->returnCallback(
            function () {
                throw new AccessDeniedException("Lorem ipsum");
            }
        ));

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(get_class($this->argumentCompiler)),
            $this->equalTo("123")
        )->willReturn($this->argumentCompiler);

        $this->controller->invokeEntityMethod($request, "123");
    }

    /**
     * @test
     */
    public function shouldRejectConstructorCalledAgain()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityInvokeController($this->controllerHelper, $this->argumentCompiler, [
            'entity-class' => get_class($this->argumentCompiler),
            'method' => 'buildArguments',
        ]);

        $controller->__construct($this->controllerHelper, $this->argumentCompiler, [
            'entity-class' => get_class($this->argumentCompiler),
            'method' => 'buildArguments',
        ]);
    }

    /**
     * @test
     */
    public function shouldRejectMissingEntityClass()
    {
        $this->expectException(InvalidArgumentException::class);

        new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'method' => 'buildArguments',
            ]
        );
    }

    /**
     * @test
     */
    public function shouldRejectMissingMethod()
    {
        $this->expectException(InvalidArgumentException::class);

        new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'entity-class' => get_class($this->argumentCompiler),
            ]
        );
    }

    /**
     * @test
     */
    public function shouldRejectNonEntityClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an existing class name. Got: "NonExistingClass"');

        new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'entity-class' => "NonExistingClass",
                'method' => 'buildArguments',
            ]
        );
    }

    /**
     * @test
     */
    public function shouldRejectNonExistingMethod()
    {
        $this->expectException(InvalidArgumentException::class);

        new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'entity-class' => get_class($this->argumentCompiler),
                'method' => 'nonExistingMethod',
            ]
        );
    }

    /**
     * @test
     */
    public function shouldRejectNonArrayArguments()
    {
        $this->expectException(InvalidArgumentException::class);

        new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'entity-class' => get_class($this->argumentCompiler),
                'method' => 'buildArguments',
                'arguments' => "12345",
            ]
        );
    }

    /**
     * @test
     */
    public function shouldBeCallableByInvokingController()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->method("get")->willReturn(123);

        $controller = new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'entity-class' => get_class($this->argumentCompiler),
                'method' => 'buildArguments',
                'arguments' => [
                    'argumentsConfiguration' => "Lorem",
                    'request' => "ipsum"
                ]
            ]
        );

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(get_class($this->argumentCompiler)),
            $this->equalTo("123")
        )->willReturn($this->argumentCompiler);

        $this->argumentCompiler->expects($this->once())->method('buildCallArguments')->with(
            $this->equalTo(new ReflectionMethod(get_class($this->argumentCompiler), 'buildArguments')),
            $this->equalTo([
                'argumentsConfiguration' => "Lorem",
                'request' => "ipsum"
            ]),
            $this->identicalTo($request)
        )->willReturn([
            ['foo' => 'bar'],
            $request
        ]);

        $this->argumentCompiler->expects($this->once())->method('buildArguments')->with(
            $this->equalTo(['foo' => 'bar']),
            $this->identicalTo($request)
        );

        $this->controllerHelper->method('getCurrentRequest')->willReturn($request);

        $controller();
    }

    /**
     * @test
     */
    public function shouldRejectCallWithoutRequest()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            [
                'entity-class' => get_class($this->argumentCompiler),
                'method' => 'buildArguments',
            ]
        );

        $this->controllerHelper->method('getCurrentRequest')->willReturn(null);

        $controller();
    }

}
