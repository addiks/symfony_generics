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

namespace Addiks\SymfonyGenerics\Controllers\API;

use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Webmozart\Assert\Assert;
use Symfony\Component\HttpFoundation\Response;
use InvalidArgumentException;

final class GenericEntityRemoveController
{

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var string|null
     */
    private $authorizationAttribute;

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        array $options
    ) {
        Assert::keyExists($options, 'entity-class');
        Assert::null($this->controllerHelper);

        $options = array_merge([
            'authorization-attribute' => null,
        ], $options);

        $this->controllerHelper = $controllerHelper;
        $this->entityClass = $options['entity-class'];
        $this->authorizationAttribute = $options['authorization-attribute'];
    }

    public function removeEntity(string $id): Response
    {
        /** @var object|null $entity */
        $entity = $this->controllerHelper->findEntity($this->entityClass, $id);

        if (is_null($entity)) {
            throw new InvalidArgumentException(sprintf(
                "Entity with id %s not found!",
                $id
            ));
        }

        if (!empty($this->authorizationAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->authorizationAttribute, $entity);
        }

        $this->controllerHelper->removeEntity($entity);

        return new Response("Entity removed!");
    }

}
