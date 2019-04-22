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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Addiks\SymfonyGenerics\Controllers\ApplyDataTemplateTrait;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use ErrorException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

final class GenericEntityListingController
{
    use ApplyDataTemplateTrait;

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentCompiler;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var array
     */
    private $criteria;

    /**
     * @var string|null
     */
    private $authorizationAttribute;

    /**
     * @var string
     */
    private $format;

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

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        ArgumentCompilerInterface $argumentCompiler,
        array $options
    ) {
        Assert::null($this->controllerHelper);
        Assert::keyExists($options, 'entity-class');
        Assert::classExists($options['entity-class']);

        $options = array_merge([
            'format' => 'json',
            'criteria' => [],
            'authorization-attribute' => null,
            'encoder' => null,
            'normalizer' => null,
            'data-template' => null,
        ], $options);

        Assert::true(is_null($options['encoder']) || $options['encoder'] instanceof EncoderInterface);
        Assert::true(is_null($options['normalizer']) || $options['normalizer'] instanceof NormalizerInterface);

        $this->controllerHelper = $controllerHelper;
        $this->argumentCompiler = $argumentCompiler;
        $this->entityClass = $options['entity-class'];
        $this->criteria = $options['criteria'];
        $this->encoder = $options['encoder'];
        $this->normalizer = $options['normalizer'];
        $this->format = $options['format'];
        $this->dataTemplate = $options['data-template'];
        $this->authorizationAttribute = $options['authorization-attribute'];
    }

    public function __invoke(): Response
    {
        /** @var Request $request */
        $request = $this->controllerHelper->getCurrentRequest();

        Assert::isInstanceOf($request, Request::class, "Cannot use controller outside of request-scope!");

        return $this->listEntities($request);
    }

    public function listEntities(Request $request): Response
    {
        if (!is_null($this->authorizationAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->authorizationAttribute, $request);
        }

        /** @var array $criteria */
        $criteria = $this->argumentCompiler->buildArguments($this->criteria, $request);

        /** @var array<object> $entities */
        $entities = $this->controllerHelper->findEntities($this->entityClass, $criteria);

        /** @var array<int, array> $resultData */
        $resultEntries = array();

        foreach ($entities as $entity) {
            /** @var object $entity */

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

            $resultEntries[] = $normalizedEntity;
        }

        /** @var string $serializedEntity */
        $serializedEntity = "";

        if ($this->encoder instanceof EncoderInterface) {
            $serializedEntity = $this->encoder->encode($resultEntries, $this->format);

        } else {
            $serializedEntity = json_encode($resultEntries);
        }

        return new Response($serializedEntity);
    }

}
