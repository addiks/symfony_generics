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
use Addiks\SymfonyGenerics\Controllers\API\GenericEntityRemoveController;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Symfony\Component\HttpFoundation\Response;
use InvalidArgumentException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Addiks\SymfonyGenerics\Tests\Unit\Controllers\SampleEntity;
use Symfony\Component\HttpFoundation\Request;
use Addiks\SymfonyGenerics\Events\EntityInteractionEvent;

final class GenericEntityRemoveControllerTest extends TestCase
{

    private GenericEntityRemoveController $controller;

    private ControllerHelperInterface $controllerHelper;

    public function setUp()
    {
        $this->controllerHelper = $this->createMock(ControllerHelperInterface::class);

        $this->controller = new GenericEntityRemoveController($this->controllerHelper, [
            'entity-class' => SampleEntity::class
        ]);
    }

    /**
     * @test
     */
    public function shouldRejectMissingEntityClass()
    {
        $this->expectException(InvalidArgumentException::class);

        new GenericEntityRemoveController($this->controllerHelper, []);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfEntityNotFound()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->controller->removeEntity("some-id");
    }

    /**
     * @test
     */
    public function shouldRemoveEntity()
    {
        $entity = new SampleEntity();

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo('some-id')
        )->willReturn($entity);

        $this->controllerHelper->expects($this->once())->method('removeEntity')->with(
            $this->identicalTo($entity)
        );

        /** @var Response $response */
        $response = $this->controller->removeEntity("some-id");

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Entity removed!', $response->getContent());
    }

    /**
     * @test
     */
    public function shouldCheckIfAccessIsGranted()
    {
        $this->expectException(AccessDeniedException::class);

        $entity = new SampleEntity();

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo("some-id")
        )->willReturn($entity);

        $this->controllerHelper->expects($this->once())->method('denyAccessUnlessGranted')->with(
            $this->equalTo('some-attribute'),
            $this->identicalTo($entity)
        )->will($this->returnCallback(
            function () {
                throw new AccessDeniedException('Lorem ipsum!');
            }
        ));

        $controller = new GenericEntityRemoveController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
            'authorization-attribute' => 'some-attribute',
        ]);

        $controller->removeEntity("some-id");
    }

    /**
     * @test
     */
    public function shouldBeCallableByInvokingController()
    {
        $entity = new SampleEntity();

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(SampleEntity::class),
            $this->equalTo('some-id')
        )->willReturn($entity);
        $this->controllerHelper->expects($this->once())->method('dispatchEvent')->with(
            $this->equalTo("symfony_generics.entity_interaction"),
            $this->equalTo(new EntityInteractionEvent(
                SampleEntity::class,
                'some-id',
                $entity,
                "__destruct"
            ))
        );

        $this->controllerHelper->expects($this->once())->method('removeEntity')->with(
            $this->identicalTo($entity)
        );

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->method("get")->willReturn('some-id');

        $this->controllerHelper->method('getCurrentRequest')->willReturn($request);

        /** @var Response $actualResponse */
        $actualResponse = ($this->controller)();

        $this->assertEquals(200, $actualResponse->getStatusCode());
        $this->assertEquals('Entity removed!', $actualResponse->getContent());
    }

    /**
     * @test
     */
    public function shouldRejectCallWithoutRequest()
    {
        $this->expectException(InvalidArgumentException::class);

        $controller = new GenericEntityRemoveController($this->controllerHelper, [
            'entity-class' => SampleEntity::class,
        ]);

        $this->controllerHelper->method('getCurrentRequest')->willReturn(null);

        $controller();
    }

}
