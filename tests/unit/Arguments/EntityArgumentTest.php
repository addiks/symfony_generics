<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\Arguments;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Arguments\EntityArgument;
use Addiks\SymfonyGenerics\Arguments\Argument;
use stdClass;
use Doctrine\Persistence\ObjectManager;

final class EntityArgumentTest extends TestCase
{

    /**
     * @test
     */
    public function shouldResolveEntityArgument()
    {
        /** @var ObjectManager $objectManager */
        $objectManager = $this->createMock(ObjectManager::class);

        /** @var Argument $id */
        $id = $this->createMock(Argument::class);
        $id->method("resolve")->willReturn("some-id");

        /** @var object $expectedEntity */
        $expectedEntity = new stdClass();

        $objectManager->expects($this->once())->method('find')->with(
            $this->identicalTo("some-entity-class"),
            $this->identicalTo("some-id")
        )->willReturn($expectedEntity);

        $argument = new EntityArgument($objectManager, "some-entity-class", $id);

        /** @var object $actualEntity */
        $actualEntity = $argument->resolve();

        $this->assertSame($actualEntity, $expectedEntity);
    }

}
