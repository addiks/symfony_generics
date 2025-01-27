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
use Psr\Container\ContainerInterface;
use Addiks\SymfonyGenerics\Controllers\API\GenericServiceInvokeController;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Addiks\SymfonyGenerics\Tests\Unit\Controllers\SampleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use InvalidArgumentException;
use ReflectionMethod;
use ErrorException;

final class GenericServiceInvokeControllerTest extends TestCase
{

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentCompiler;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp(): void
    {
        $this->controllerHelper = $this->createMock(ControllerHelperInterface::class);
        $this->argumentCompiler = $this->createMock(ArgumentCompilerInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @test
     */
    public function shouldFailIfServiceIsMissing()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericServiceInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            $this->container,
            [
                'method' => 'doFoo'
            ]
        );
    }

    /**
     * @test
     */
    public function shouldFailIfMethodIsMissing()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericServiceInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            $this->container,
            [
                'service' => 'some_service',
            ]
        );
    }

    /**
     * @test
     */
    public function shouldCallService()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var SampleService $service */
        $service = $this->createMock(SampleService::class);

        $service->expects($this->once())->method('doFoo')->with(
            $this->equalTo('lorem'),
            $this->equalTo('ipsum')
        );

        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some_service')
        )->willReturn($service);

        $this->controllerHelper->expects($this->once())->method('flushORM');

        $this->argumentCompiler->expects($this->once())->method('buildCallArguments')->with(
            $this->equalTo(new ReflectionMethod($service, 'doFoo')),
            $this->equalTo(['lorem' => 'ipsum'])
        )->willReturn(['lorem', 'ipsum']);

        /** @var string $expectedResponseContent */
        $expectedResponseContent = "Service call completed";

        $controller = new GenericServiceInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            $this->container,
            [
                'service' => 'some_service',
                'method' => 'doFoo',
                'arguments' => ['lorem' => 'ipsum']
            ]
        );

        /** @var Response $actualResponse */
        $actualResponse = $controller->callService($request);

        $this->assertEquals($expectedResponseContent, $actualResponse->getContent());
    }

    /**
     * @test
     */
    public function shouldRedirectWhenNeeded()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var SampleService $service */
        $service = $this->createMock(SampleService::class);

        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some_service')
        )->willReturn($service);

        $this->argumentCompiler->expects($this->once())->method('buildCallArguments')->with(
            $this->equalTo(new ReflectionMethod($service, 'doFoo')),
            $this->equalTo(['lorem' => 'ipsum'])
        )->willReturn(['lorem', 'ipsum']);

        $controller = new GenericServiceInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            $this->container,
            [
                'service' => 'some_service',
                'method' => 'doFoo',
                'arguments' => ['lorem' => 'ipsum'],
                'success-redirect' => 'some_redirect_route'
            ]
        );

        $this->controllerHelper->expects($this->once())->method('redirectToRoute')->with(
            $this->equalTo("some_redirect_route"),
            $this->equalTo([]),
            $this->equalTo(303)
        );

        $controller->callService($request);

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
            $this->equalTo('bar'),
            $this->identicalTo($request)
        )->will($this->returnCallback(
            function () {
                throw new AccessDeniedException("Lorem ipsum");
            }
        ));

        $controller = new GenericServiceInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            $this->container,
            [
                'service' => 'some_service',
                'method' => 'doFoo',
                'authorization-attributes' => 'bar'
            ]
        );

        $controller->callService($request);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfServiceNotFound()
    {
        $this->expectException(ErrorException::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some_service')
        )->willReturn(null);

        $controller = new GenericServiceInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            $this->container,
            [
                'service' => 'some_service',
                'method' => 'doFoo',
                'arguments' => ['lorem' => 'ipsum']
            ]
        );

        $controller->callService($request);
    }

    /**
     * @test
     */
    public function shouldBeCallableByInvokingController()
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var SampleService $service */
        $service = $this->createMock(SampleService::class);

        $service->expects($this->once())->method('doFoo')->with(
            $this->equalTo('lorem'),
            $this->equalTo('ipsum')
        );

        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some_service')
        )->willReturn($service);

        $this->argumentCompiler->expects($this->once())->method('buildCallArguments')->with(
            $this->equalTo(new ReflectionMethod($service, 'doFoo')),
            $this->equalTo(['lorem' => 'ipsum'])
        )->willReturn(['lorem', 'ipsum']);

        /** @var string $expectedResponseContent */
        $expectedResponseContent = "Service call completed";

        $controller = new GenericServiceInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            $this->container,
            [
                'service' => 'some_service',
                'method' => 'doFoo',
                'arguments' => ['lorem' => 'ipsum']
            ]
        );

        $this->controllerHelper->method('getCurrentRequest')->willReturn($request);

        /** @var Response $actualResponse */
        $actualResponse = $controller();

        $this->assertEquals($expectedResponseContent, $actualResponse->getContent());
    }

    /**
     * @test
     */
    public function shouldRejectCallWithoutRequest()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericServiceInvokeController(
            $this->controllerHelper,
            $this->argumentCompiler,
            $this->container,
            [
                'service' => 'some_service',
                'method' => 'doFoo',
            ]
        );

        $this->controllerHelper->method('getCurrentRequest')->willReturn(null);

        $controller();
    }

}
