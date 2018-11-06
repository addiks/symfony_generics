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

namespace Addiks\SymfonyGenerics\Controllers;

use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use InvalidArgumentException;
use Webmozart\Assert\Assert;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use DOMDocument;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use ErrorException;

final class GenericEntityFetchController
{

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
            'authorization-attribute' => null
        ], $options);

        Assert::true(is_null($options['encoder']) || $options['encoder'] instanceof EncoderInterface);
        Assert::true(is_null($options['normalizer']) || $options['normalizer'] instanceof NormalizerInterface);
        Assert::classExists($options['entity-class']);

        $this->controllerHelper = $controllerHelper;
        $this->encoder = $options['encoder'];
        $this->normalizer = $options['normalizer'];
        $this->entityClass = $options['entity-class'];
        $this->format = $options['format'];
        $this->dataTemplate = $options['data-template'];
        $this->authorizationAttribute = $options['authorization-attribute'];
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

    private function applyDataTemplate(array $data, array $dataTemplate): array
    {
        /** @var array $result */
        $result = array();

        foreach ($dataTemplate as $key => $templateEntry) {
            /** @var string|array $templateEntry */

            /** @var mixed $entryResult */
            $entryResult = null;

            if (is_string($templateEntry)) {
                $entryResult = $this->extractValueFromDataArray($data, explode(".", $templateEntry));

            } elseif (is_array($templateEntry)) {
                $entryResult = $this->applyDataTemplate($data, $templateEntry);

            } else {
                throw new ErrorException("Invalid entry for data-template, must be string or array!");
            }

            $result[$key] = $entryResult;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    private function extractValueFromDataArray(array $data, array $path)
    {
        /** @var string $key */
        $key = array_shift($path);

        /** @var mixed $value */
        $value = null;

        if (isset($data[$key])) {
            $value = $data[$key];

            if (!empty($path)) {
                $value = $this->extractValueFromDataArray($value, $path);
            }
        }

        return $value;
    }

}
