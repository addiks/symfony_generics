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

namespace Addiks\SymfonyGenerics\Events;

use Symfony\Component\EventDispatcher\Event;
use Webmozart\Assert\Assert;

final class EntityInteractionEvent extends Event
{

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var string|null
     */
    private $entityId;

    /**
     * @var object|null
     */
    private $entity;

    /**
     * @var string|null
     */
    private $method;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @param object|null $entity
     */
    public function __construct(
        string $entityClass,
        string $entityId = null,
        $entity = null,
        string $method = null,
        array $arguments = array()
    ) {
        Assert::true(class_exists($entityClass));
        Assert::true(is_object($entity) || is_null($entity));

        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->entity = $entity;
        $this->method = $method;
        $this->arguments = $arguments;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    /**
     * @return object|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

}
