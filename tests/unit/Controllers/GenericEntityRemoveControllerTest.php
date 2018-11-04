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
use Addiks\SymfonyGenerics\Controllers\GenericEntityRemoveController;
use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use InvalidArgumentException;

final class GenericEntityRemoveControllerTest extends TestCase
{

    /**
     * @var GenericEntityRemoveController
     */
    private $controller;

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    public function setUp()
    {
        $this->controllerHelper = $this->createMock(ControllerHelperInterface::class);

        $this->controller = new GenericEntityRemoveController($this->controllerHelper, [
            'entity-class' => stdClass::class
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
    public function shouldRejectControllerCalledAgain()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->controller->__construct($this->controllerHelper, [
            'entity-class' => stdClass::class
        ]);
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
        $entity = new stdClass();

        $this->controllerHelper->expects($this->once())->method('findEntity')->with(
            $this->equalTo(stdClass::class),
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

}
