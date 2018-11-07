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
use Webmozart\Assert\Assert;
use Psr\Container\ContainerInterface;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use ErrorException;
use ReflectionObject;
use ReflectionMethod;

final class GenericServiceInvokeController
{

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentCompiler;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $arguments;

    /**
     * @var string|null
     */
    private $authorizationAttribute;

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        ArgumentCompilerInterface $argumentCompiler,
        ContainerInterface $container,
        array $options
    ) {
        Assert::null($this->controllerHelper);
        Assert::keyExists($options, 'service');
        Assert::keyExists($options, 'method');

        $options = array_merge([
            'arguments' => [],
            'authorization-attributes' => null,
        ], $options);

        $this->controllerHelper = $controllerHelper;
        $this->argumentCompiler = $argumentCompiler;
        $this->container = $container;
        $this->serviceId = $options['service'];
        $this->method = $options['method'];
        $this->arguments = $options['arguments'];
        $this->authorizationAttribute = $options['authorization-attributes'];
    }

    public function callService(Request $request): Response
    {
        if (!is_null($this->authorizationAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->authorizationAttribute, $request);
        }

        /** @var object|null $service */
        $service = $this->container->get($this->serviceId);

        if (is_null($service)) {
            throw new ErrorException(sprintf(
                "Could not find service '%s'!",
                $this->serviceId
            ));
        }

        $reflectionObject = new ReflectionObject($service);

        /** @var ReflectionMethod $reflectionMethod */
        $reflectionMethod = $reflectionObject->getMethod($this->method);

        /** @var array $arguments */
        $arguments = $this->argumentCompiler->buildCallArguments(
            $reflectionMethod,
            $this->arguments,
            $request
        );

        $reflectionMethod->invokeArgs($service, $arguments);

        return new Response("Service call completed");
    }

}
