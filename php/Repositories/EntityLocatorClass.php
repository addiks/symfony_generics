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

namespace Addiks\SymfonyGenerics\Repositories;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntityLocatorClass implements EntityLocator
{

    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;

    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $entityManager
    ) {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    public function findEntity(
        string $requestKey,
        string $entityClass,
        bool $failIfNotFound = true
    ): ?object {
        /** @var object|null $entity */
        $entity = null;

        /** @var Request|null */
        $request = $this->requestStack->getCurrentRequest();

        if (is_object($request)) {
            /** @var string $entityId */
            $entityId = $request->get($requestKey);

            if (!empty($entityId)) {
                $entity = $this->entityManager->find($entityClass, $entityId);
            }
        }

        if ($failIfNotFound && is_null($entity)) {
            throw new NotFoundHttpException(sprintf(
                "Cound not find entity with id '%s'!",
                $entityId
            ));
        }

        return $entity;
    }

}
