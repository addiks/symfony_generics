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

use Addiks\SymfonyGenerics\Arguments\Argument;
use BackedEnum;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use UnitEnum;
use ValueError;

final class EntityArgument implements Argument
{

    /** @var class-string */
    private string $entityClass;

    private Argument $id;

    private ObjectManager $objectManager;

    private ?ObjectRepository $repository;

    /** @var array<string, mixed> */
    private static $constantMap = array(
        'true' => true,
        'false' => false,
        'null' => null,
    );

    /** @param class-string $entityClass */
    public function __construct(
        ObjectManager $objectManager,
        string $entityClass,
        Argument $id
    ) {
        $this->entityClass = $entityClass;
        $this->id = $id;
        $this->objectManager = $objectManager;
    }

    public function resolve()
    {
        /** @var string $entityId */
        $entityId = $this->id->resolve();

        if (preg_match("/^\[([a-zA-Z0-9_-]+)\=(.*)\]$/is", $entityId, $matches)) {
            [, $column, $value] = $matches;

            if (isset(self::$constantMap[strtolower($value)])) {
                $value = self::$constantMap[strtolower($value)];
            }

            return $this->repository()->findOneBy([$column => $value]);

        } else {
            if (empty($entityId)) {
                return null;
            }
            
            if (is_a($this->entityClass, BackedEnum::class, true)) {
                try {
                    return ($this->entityClass)::from($entityId);
                    
                } catch (ValueError $error) {
                    return constant($this->entityClass . '::' . $entityId);
                }
                
            } elseif (is_a($this->entityClass, UnitEnum::class, true)) {
                return constant($this->entityClass . '::' . $entityId);
                
            } else {
                return $this->objectManager->find(
                    $this->entityClass,
                    $entityId
                );
            }
        }
    }

    private function repository(): ObjectRepository
    {
        if (is_null($this->repository)) {
            $this->repository = $this->objectManager->getRepository($this->entityClass);
        }

        return $this->repository;
    }

}
