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
use Addiks\SymfonyGenerics\Controllers\API\GenericEntityCreateController;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Symfony\Component\HttpFoundation\Request;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Addiks\SymfonyGenerics\Tests\Unit\Controllers\SampleEntity;
use Serializable;
use stdClass;
use InvalidArgumentException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use ReflectionException;
use ErrorException;
use Addiks\SymfonyGenerics\Events\EntityInteractionEvent;

final class GenericEntityCreateControllerTest extends TestCase
{

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentBuilder;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->controllerHelper = $this->createMock(ControllerHelperInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->argumentBuilder = $this->createMock(ArgumentCompilerInterface::class);
    }

    /**
     * @test
     */
    public function shouldCreateAnEntity()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class
            ]
        );

        $this->controllerHelper->expects($this->once())->method('flushORM');
        $this->controllerHelper->expects($this->once())->method('dispatchEvent')->with(
            $this->equalTo('symfony_generics.entity_interaction'),
            $this->equalTo(new EntityInteractionEvent(
                SampleEntity::class,
                null, # TODO: get id via reflection
                new SampleEntity(),
                "__construct",
                []
            ))
        );
        $this->controllerHelper->expects($this->once())->method('persistEntity')->with(
            $this->equalTo(new SampleEntity())
        );

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Response $response */
        $response = $controller->createEntity($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldRedirectWhenNeeded()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'success-redirect' => 'some_route'
            ]
        );

        $this->controllerHelper->expects($this->once())->method('flushORM');
        $this->controllerHelper->expects($this->once())->method('persistEntity')->with(
            $this->equalTo(new SampleEntity())
        );

        $expectedResponse = new Response();

        $this->controllerHelper->expects($this->once())->method('redirectToRoute')->with(
            $this->equalTo('some_route'),
            $this->equalTo([
                'entityId' => 'some_id'
            ]),
            $this->equalTo(303)
        )->willReturn($expectedResponse);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Response $actualResponse */
        $actualResponse = $controller->createEntity($request);

        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @test
     */
    public function shouldRejectMissingEntityClass()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
            ]
        );
    }

    /**
     * @test
     */
    public function shouldRejectControllerCalledAgain()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class
            ]
        );

        $this->expectException(InvalidArgumentException::class);

        $controller->__construct(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class
            ]
        );
    }

    /**
     * @test
     */
    public function shouldProvideConstructArguments()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'arguments' => [
                    'foo' => 'bar'
                ]
            ]
        );

        /** @var SampleEntity|null $persistedEntity */
        $persistedEntity = null;

        $this->controllerHelper->expects($this->once())->method('flushORM');
        $this->controllerHelper->method('persistEntity')->will($this->returnCallback(
            function (SampleEntity $entity) use (&$persistedEntity) {
                $persistedEntity = $entity;
            }
        ));

        $this->argumentBuilder->method('buildCallArguments')->willReturn([
            'bar'
        ]);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var Response $response */
        $response = $controller->createEntity($request);

        $this->assertEquals('bar', $persistedEntity->constructArgument);
    }

    /**
     * @test
     */
    public function shouldExecuteACall()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'calls' => [
                    'doFoo' => []
                ]
            ]
        );

        /** @var SampleEntity|null $persistedEntity */
        $persistedEntity = null;

        $this->controllerHelper->method('persistEntity')->will($this->returnCallback(
            function (SampleEntity $entity) use (&$persistedEntity) {
                $persistedEntity = $entity;
            }
        ));

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->assertNull($persistedEntity);

        $controller->createEntity($request);

        $this->assertTrue($persistedEntity instanceof SampleEntity);
        $this->assertTrue($persistedEntity->fooCalled);
    }

    /**
     * @test
     */
    public function shouldRejectCallToNonExistingMethod()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'calls' => [
                    'doNonExistingThing' => []
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function shouldRejectCallWithNonArrayParameters()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'calls' => [
                    'doFoo' => "notAnArray"
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function shouldRejectCallWithIntegerMethod()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'calls' => [
                    0 => []
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function shouldUseFactoryObject()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'factory' => '@some_factory_service::serialize'
            ]
        );

        $expectedEntity = new SampleEntity();

        /** @var SampleEntity|null $actualEntity */
        $actualEntity = null;

        /** @var Serializable $factoryMock */
        $factoryMock = $this->createMock(Serializable::class);
        $factoryMock->method("serialize")->willReturn($expectedEntity);

        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some_factory_service')
        )->willReturn($factoryMock);

        $this->controllerHelper->method('persistEntity')->will($this->returnCallback(
            function (SampleEntity $entity) use (&$actualEntity) {
                $actualEntity = $entity;
            }
        ));

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $controller->createEntity($request);

        $this->assertSame($expectedEntity, $actualEntity);
    }

    /**
     * @test
     */
    public function shouldRejectWrongEntityCreated()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'factory' => '@some_factory_service::serialize'
            ]
        );

        /** @var Serializable $factoryMock */
        $factoryMock = $this->createMock(Serializable::class);
        $factoryMock->method("serialize")->willReturn(new stdClass());

        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some_factory_service')
        )->willReturn($factoryMock);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->expectException(InvalidArgumentException::class);

        $controller->createEntity($request);
    }

    /**
     * @test
     */
    public function shouldRejectNonExistingFactory()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'factory' => '@some_factory_service::serialize'
            ]
        );

        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some_factory_service')
        )->willReturn(null);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Did not find service 'some_factory_service'!");

        $controller->createEntity($request);
    }

    /**
     * @test
     */
    public function shouldRejectInvalidFactory()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'factory' => '::serialize'
            ]
        );

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->expectException(ErrorException::class);

        $controller->createEntity($request);
    }

    /**
     * @test
     */
    public function shouldRejectNonExistingFactoryMethod()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'factory' => '@some_factory_service::MethodDoesNotExist'
            ]
        );

        /** @var Serializable $factoryMock */
        $factoryMock = $this->createMock(Serializable::class);

        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some_factory_service')
        )->willReturn($factoryMock);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->expectException(ReflectionException::class);

        $controller->createEntity($request);
    }

    /**
     * @test
     */
    public function shouldCorrectlyDetectFactoryMethod()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'factory' => '@some_factory_service::serialize::thisShouldCauseAnError'
            ]
        );

        /** @var Serializable $factoryMock */
        $factoryMock = $this->createMock(Serializable::class);
        $factoryMock->method("serialize")->willReturn(new SampleEntity());

        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some_factory_service')
        )->willReturn($factoryMock);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->expectException(ReflectionException::class);

        $controller->createEntity($request);
    }

    /**
     * @test
     */
    public function shouldCheckIfAccessIsGranted()
    {
        $this->expectException(AccessDeniedException::class);

        $this->controllerHelper->expects($this->exactly(2))->method('denyAccessUnlessGranted')->will($this->returnCallback(
            function ($attribute, $object) {
                if ($object instanceof SampleEntity) {
                    throw new AccessDeniedException('Lorem ipsum!');
                }
            }
        ));

        /** @var Serializable $factoryMock */
        $factoryMock = $this->createMock(Serializable::class);
        $factoryMock->method("serialize")->willReturn(new SampleEntity());

        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some_factory_service')
        )->willReturn($factoryMock);

        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'factory' => '@some_factory_service::serialize',
                'authorization-attribute' => 'some-attribute',
            ]
        );

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $controller->createEntity($request);
    }

    /**
     * @test
     */
    public function shouldBeCallableByInvokingController()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class
            ]
        );

        $this->controllerHelper->expects($this->once())->method('flushORM');
        $this->controllerHelper->expects($this->once())->method('persistEntity')->with(
            $this->equalTo(new SampleEntity())
        );

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        $this->controllerHelper->method('getCurrentRequest')->willReturn($request);

        /** @var Response $response */
        $response = $controller();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function shouldRejectCallWithoutRequest()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
            ]
        );

        $this->controllerHelper->method('getCurrentRequest')->willReturn(null);

        $controller();
    }

}
