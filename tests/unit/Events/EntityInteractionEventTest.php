<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Events;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Events\EntityInteractionEvent;
use stdClass;
use InvalidArgumentException;

final class EntityInteractionEventTest extends TestCase
{

    /**
     * @var EntityInteractionEvent
     */
    private $event;

    /**
     * @var stdClass
     */
    private $entity;

    public function setUp()
    {
        $this->entity = $this->createMock(stdClass::class);

        $this->event = new EntityInteractionEvent(
            "stdClass",
            "some-entity-id",
            $this->entity,
            "someMethod",
            ['foo', 'bar']
        );
    }

    /**
     * @test
     */
    public function shouldRejectNonExistingClass()
    {
        $this->expectException(InvalidArgumentException::class);

        new EntityInteractionEvent(
            "doesNotExist",
            "some-entity-id",
            $this->entity,
            "someMethod",
            ['foo', 'bar']
        );
    }

    /**
     * @test
     */
    public function shouldRejectNonObjectEntity()
    {
        $this->expectException(InvalidArgumentException::class);

        new EntityInteractionEvent(
            "stdClass",
            "some-entity-id",
            "entity",
            "someMethod",
            ['foo', 'bar']
        );
    }

    /**
     * @test
     */
    public function shouldHaveEntityClass()
    {
        $this->assertEquals("stdClass", $this->event->getEntityClass());
    }

    /**
     * @test
     */
    public function shouldHaveEntityId()
    {
        $this->assertEquals("some-entity-id", $this->event->getEntityId());
    }

    /**
     * @test
     */
    public function shouldHaveEntity()
    {
        $this->assertSame($this->entity, $this->event->getEntity());
    }

    /**
     * @test
     */
    public function shouldHaveMethod()
    {
        $this->assertEquals("someMethod", $this->event->getMethod());
    }

    /**
     * @test
     */
    public function shouldHaveArguments()
    {
        $this->assertEquals(['foo', 'bar'], $this->event->getArguments());
    }

}
