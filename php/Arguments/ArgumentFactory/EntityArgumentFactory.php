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

namespace Addiks\SymfonyGenerics\Arguments\ArgumentFactory;

use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;
use Addiks\SymfonyGenerics\Arguments\Argument;
use Addiks\SymfonyGenerics\Arguments\EntityArgument;
use Doctrine\Common\Persistence\ObjectManager;
use Webmozart\Assert\Assert;

final class EntityArgumentFactory implements ArgumentFactory
{

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ArgumentFactory
     */
    private $argumentFactory;

    public function __construct(
        ObjectManager $objectManager,
        ArgumentFactory $argumentFactory
    ) {
        $this->objectManager = $objectManager;
        $this->argumentFactory = $argumentFactory;
    }

    public function understandsString(string $source): bool
    {
        return strpos($source, '#') > 0;
    }

    public function understandsArray(array $source): bool
    {
        return isset($source['entity-class']) && isset($source['entity-id']);
    }

    public function createArgumentFromString(string $source): Argument
    {
        Assert::contains($source, '#');

        [$entityClass, $idSource] = explode('#', $source);

        /** @var Argument $id */
        $id = $this->argumentFactory->createArgumentFromString($idSource);

        return new EntityArgument($this->objectManager, $entityClass, $id);
    }

    public function createArgumentFromArray(array $source): Argument
    {
        Assert::keyExists($source, 'entity-class');
        Assert::keyExists($source, 'entity-id');

        /** @var Argument $id */
        $id = $this->argumentFactory->createArgumentFromString($source['entity-id']);

        return new EntityArgument($this->objectManager, $source['entity-class'], $id);
    }

}