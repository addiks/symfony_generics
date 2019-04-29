<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Arguments;

use Addiks\SymfonyGenerics\Arguments\ArgumentInterface;
use Doctrine\Common\Persistence\ObjectManager;

final class EntityArgument implements ArgumentInterface
{

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var ArgumentInterface
     */
    private $id;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(
        ObjectManager $objectManager,
        string $entityClass,
        ArgumentInterface $id
    ) {
        $this->entityClass = $entityClass;
        $this->id = $id;
        $this->objectManager = $objectManager;
    }

    public function getValue()
    {
        return $this->objectManager->find(
            $this->entityClass,
            $this->id->getValue()
        );
    }

}