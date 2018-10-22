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
use Addiks\SymfonyGenerics\Controllers\GenericEntityCreateController;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Symfony\Component\HttpFoundation\Request;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Addiks\SymfonyGenerics\Tests\Unit\Controllers\SampleEntity;
use Webmozart\Assert\Assert;
use Serializable;

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
    public function shouldProvideConstructArguments()
    {
        $controller = new GenericEntityCreateController(
            $this->controllerHelper,
            $this->argumentBuilder,
            $this->container,
            [
                'entity-class' => SampleEntity::class,
                'calls' => [
                    'construct' => [
                        'foo' => 'bar'
                    ]
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

}
