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
use InvalidArgumentException;
use Webmozart\Assert\Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use ErrorException;
use Addiks\SymfonyGenerics\Controllers\ApplyDataTemplateTrait;
use Addiks\SymfonyGenerics\Events\EntityInteractionEvent;
use Symfony\Component\HttpFoundation\Request;

final class GenericEntityFetchController
{
    use ApplyDataTemplateTrait;

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var EncoderInterface|null
     */
    private $encoder;

    /**
     * @var NormalizerInterface|null
     */
    private $normalizer;

    /**
     * @var array|null
     */
    private $dataTemplate;

    /**
     * @var string|null
     */
    private $authorizationAttribute;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var string
     */
    private $entityIdKey;

    /**
     * @var string
     */
    private $format;

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        array $options
    ) {
        Assert::null($this->controllerHelper);
        Assert::keyExists($options, 'entity-class');

        $options = array_merge([
            'format' => 'json',
            'encoder' => null,
            'normalizer' => null,
            'data-template' => null,
            'authorization-attribute' => null,
            'entity-id-key' => 'entityId',
        ], $options);

        Assert::true(is_null($options['encoder']) || $options['encoder'] instanceof EncoderInterface);
        Assert::true(is_null($options['normalizer']) || $options['normalizer'] instanceof NormalizerInterface);
        Assert::classExists($options['entity-class']);

        $this->controllerHelper = $controllerHelper;
        $this->entityIdKey = $options['entity-id-key'];
        $this->encoder = $options['encoder'];
        $this->normalizer = $options['normalizer'];
        $this->entityClass = $options['entity-class'];
        $this->format = $options['format'];
        $this->dataTemplate = $options['data-template'];
        $this->authorizationAttribute = $options['authorization-attribute'];
    }

    public function __invoke(): Response
    {
        /** @var Request $request */
        $request = $this->controllerHelper->getCurrentRequest();

        Assert::isInstanceOf($request, Request::class, "Cannot use controller outside of request-scope!");

        /** @var string $entityId */
        $entityId = $request->get($this->entityIdKey);

        return $this->fetchEntity($entityId);
    }

    public function fetchEntity(string $entityId): Response
    {
        /** @var object|null $entity */
        $entity = $this->controllerHelper->findEntity($this->entityClass, $entityId);

        if (is_null($entity)) {
            throw new InvalidArgumentException(sprintf(
                "Could not find entity with id '%s'!",
                $entityId
            ));
        }

        if (!empty($this->authorizationAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->authorizationAttribute, $entity);
        }

        $this->controllerHelper->dispatchEvent("symfony_generics.entity_interaction", new EntityInteractionEvent(
            $this->entityClass,
            $entityId,
            $entity,
            "*FETCH*"
        ));

        /** @var array $normalizedEntity */
        $normalizedEntity = array();

        if ($this->normalizer instanceof NormalizerInterface) {
            $normalizedEntity = $this->normalizer->normalize($entity);

        } else {
            $normalizer = new ObjectNormalizer();

            $normalizedEntity = $normalizer->normalize($entity);
        }

        if (!is_array($normalizedEntity)) {
            throw new ErrorException("Result of normalize process must be an array!");
        }

        if (!is_null($this->dataTemplate)) {
            $normalizedEntity = $this->applyDataTemplate($normalizedEntity, $this->dataTemplate);
        }

        /** @var string $serializedEntity */
        $serializedEntity = "";

        if ($this->encoder instanceof EncoderInterface) {
            $serializedEntity = $this->encoder->encode($normalizedEntity, $this->format);

        } else {
            $serializedEntity = json_encode($normalizedEntity);
        }

        return new Response($serializedEntity);
    }

}
