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
use Psr\Container\ContainerInterface;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use ErrorException;
use ReflectionObject;
use ReflectionMethod;
use Throwable;
use Addiks\SymfonyGenerics\SelfValidating;
use Addiks\SymfonyGenerics\SelfValidateTrait;
use Symfony\Component\Form\FormInterface;
use InvalidArgumentException;

final class GenericServiceInvokeController implements SelfValidating
{
    use SelfValidateTrait;

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
     * @var FormInterface|null
     */
    private $argumentsForm;

    /**
     * @var string|null
     */
    private $authorizationAttribute;

    /**
     * @var string|null
     */
    private $successRedirectRoute;

    /**
     * @var array
     */
    private $successRedirectArguments;

    /**
     * @var integer
     */
    private $successRedirectStatus;

    /**
     * @var bool
     */
    private $sendReturnValueInResponse = false;
    
    private $returnValueInResponseGetter = '';

    /**
     * @var string
     */
    private $successFlashMessage;

    /**
     * @var array<string, string>
     */
    private $successResponseHeader = array();

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        ArgumentCompilerInterface $argumentCompiler,
        ContainerInterface $container,
        array $options
    ) {
        Assert::null($this->controllerHelper);
        Assert::keyExists($options, 'service');
        Assert::keyExists($options, 'method');

        /** @var int $defaultRedirectStatus */
        $defaultRedirectStatus = 303;

        $options = array_merge([
            'arguments' => [],
            'arguments-form' => null,
            'authorization-attributes' => null,
            'success-redirect' => null,
            'success-redirect-arguments' => [],
            'success-redirect-status' => $defaultRedirectStatus,
            'success-flash-message' => "",
            'send-return-value-in-response' => false,
            'return-value-in-response-getter' => '',
            'success-response-header' => [],
        ], $options);
        
        if (!empty($options['return-value-in-response-getter'])) {
            $options['send-return-value-in-response'] = true;
        }

        $this->controllerHelper = $controllerHelper;
        $this->argumentCompiler = $argumentCompiler;
        $this->container = $container;
        $this->serviceId = $options['service'];
        $this->method = $options['method'];
        $this->arguments = $options['arguments'];
        $this->argumentsForm = $options['arguments-form'];
        $this->authorizationAttribute = $options['authorization-attributes'];
        $this->successRedirectRoute = $options['success-redirect'];
        $this->successRedirectArguments = $options['success-redirect-arguments'];
        $this->successRedirectStatus = $options['success-redirect-status'];
        $this->successFlashMessage = $options['success-flash-message'];
        $this->sendReturnValueInResponse = $options['send-return-value-in-response'];
        $this->returnValueInResponseGetter = $options['return-value-in-response-getter'];
        $this->successResponseHeader = $options['success-response-header'];
    }

    public function __invoke(): Response
    {
        /** @var Request $request */
        $request = $this->controllerHelper->getCurrentRequest();

        Assert::isInstanceOf($request, Request::class, "Cannot use controller outside of request-scope!");

        return $this->callService($request);
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
            $this->arguments
        );
        
        if (is_object($this->argumentsForm)) {
            $this->argumentsForm->handleRequest($request);
            
            Assert::true($this->argumentsForm->isSubmitted());
            Assert::true($this->argumentsForm->isValid());
            
            $arguments = array_merge(array_values($this->argumentsForm->getData()), $arguments);
        }

        /** @var mixed $returnValue */
        $returnValue = $reflectionMethod->invokeArgs($service, $arguments);

        $this->controllerHelper->flushORM();

        if (!empty($this->successFlashMessage)) {
            $this->controllerHelper->addFlashMessage($this->successFlashMessage, "success");
        }

        if (!empty($this->successRedirectRoute)) {
            /** @var array $redirectArguments */
            $redirectArguments = $this->argumentCompiler->buildArguments($this->successRedirectArguments);

            return $this->controllerHelper->redirectToRoute(
                $this->successRedirectRoute,
                $redirectArguments,
                $this->successRedirectStatus
            );
        }

        /** @var Response $response */
        $response = null;

        if ($this->sendReturnValueInResponse) {
            if (!empty($this->returnValueInResponseGetter)) {
                foreach (explode('.', $this->returnValueInResponseGetter) as $getterKey) {
                    if (is_array($returnValue)) {
                        if (isset($returnValue[$getterKey])) {
                            $returnValue = $returnValue[$getterKey];
                            
                        } else {
                            throw new InvalidArgumentException(sprintf(
                                'Key "%s" does not exist in result (sub-) array!',
                                $getterKey
                            ));
                        }
                        
                    } elseif (is_object($returnValue)) {
                        if (method_exists($returnValue, $getterKey)) {
                            $returnValue = $returnValue->{$getterKey}();
                            
                        } elseif (property_exists($returnValue, $getterKey)) {
                            $returnValue = $returnValue->{$getterKey};
                            
                        } else {
                            throw new InvalidArgumentException(sprintf(
                                'Method or property "%s" does not exist in result (sub-) object!',
                                $getterKey
                            ));
                        }
                        
                    } else {
                        throw new InvalidArgumentException(
                            'Cannot get sub-value from value that is neither an array nor an object!'
                        );
                    }
                }
            }
            
            $response = new Response((string)$returnValue);

        } else {
            $response = new Response("Service call completed");
        }

        $response->headers->add($this->successResponseHeader);

        return $response;
    }

    public function isSelfValid(?string &$reason = null): bool
    {
        try {
            /** @var object $service */
            $service = $this->container->get($this->serviceId);

            if (!is_object($service)) {
                $reason = sprintf(
                    "Could not find service '%s'!",
                    $this->serviceId
                );
                return false;
            }

            $reflectionObject = new ReflectionObject($service);

            /** @var ReflectionMethod $refletionMethod */
            $refletionMethod = $reflectionObject->getMethod($this->method);

            return $this->areArgumentsCompatibleWithReflectionMethod($refletionMethod, $this->arguments);

        } catch (Throwable $exception) {
            $reason = $exception->getMessage();

            return false;
        }
    }

    protected function buildInvalidMessage(string $reason): string
    {
        return sprintf(
            'Configuration of Service-Invoke-Controller for "%s::%s" is invalid: "%s"!',
            $this->serviceId,
            $this->method,
            $reason
        );
    }

}
