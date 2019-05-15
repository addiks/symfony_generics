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

namespace Addiks\SymfonyGenerics\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\Assert\Assert;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use ErrorException;

/**
 * This compiler-pass enables to have one service that can decorate multiple different other services.
 * The most prominent use-case would be to attach the same error-handling to multiple different controllers.
 *
 * TODO: extract definition-filter and argument-filter into their own objects/classes.
 */
final class DecoratorTemplateCompilerPass implements CompilerPassInterface
{

    /**
     * @var string
     */
    private $tagNameDecorates;

    public function __construct(
        string $tagNameDecorates = "symfony_generics.decorates"
    ) {
        $this->tagNameDecorates = $tagNameDecorates;
    }

    public function process(ContainerBuilder $container): void
    {
        /** @var array<string, array<int, array<string, string>>> $decoratesTags */
        $decoratesTags = $container->findTaggedServiceIds($this->tagNameDecorates);

        foreach ($decoratesTags as $decoratorServiceId => $decorates) {
            /** @var array<array<string, string>> $decorates */

            foreach ($decorates as $decorate) {
                /** @var array<string, string> $decorate */

                Assert::keyExists($decorate, 'decorates', sprintf(
                    "Missing key 'decorates' (containing the decorated-service-id) on tag '%s' in decorator '%s'!",
                    $this->tagNameDecorates,
                    $decoratorServiceId
                ));

                /** @var string $decoratedServiceId */
                $decoratedServiceId = $decorate['decorates'];

                unset($decorate['decorates']);

                $this->applyDecorate($container, $decoratorServiceId, $decoratedServiceId, $decorate);
            }

            $container->removeDefinition($decoratorServiceId);
        }
    }

    private function applyDecorate(
        ContainerBuilder $container,
        string $decoratorServiceId,
        string $decoratedServiceId,
        array $parameters
    ): void {
        /** @var Definition $decoratorDefinition */
        $decoratorDefinition = $container->getDefinition($decoratorServiceId);

        /** @var array<string, mixed> $newDecoratorTags */
        $newDecoratorTags = $decoratorDefinition->getTags();

        Assert::keyExists($newDecoratorTags, $this->tagNameDecorates);
        unset($newDecoratorTags[$this->tagNameDecorates]);

        $decoratorDefinition->setTags($newDecoratorTags);

        /** @var string $newDecoratorServiceId */
        $newDecoratorServiceId = sprintf(
            "%s.%s.%s",
            $decoratedServiceId,
            $this->tagNameDecorates,
            $decoratorServiceId
        );

        /** @var Definition $newDecoratorDefinition */
        $newDecoratorDefinition = $this->filterDefinition(
            $decoratorDefinition,
            $parameters,
            $decoratorServiceId,
            $newDecoratorServiceId
        );

        $newDecoratorDefinition->setDecoratedService($decoratedServiceId);

        $container->setDefinition($newDecoratorServiceId, $newDecoratorDefinition);
    }

    private function filterDefinition(
        Definition $oldDefinition,
        array $parameters,
        string $decoratorServiceId,
        string $newDecoratorServiceId
    ): Definition {
        /** @var Definition $newDefinition */
        $newDefinition = clone $oldDefinition;

        /** @var array<int, Reference|array|string> $newArguments */
        $newArguments = array();

        foreach ($oldDefinition->getArguments() as $key => $oldArgument) {
            /** @var Reference|array|string $oldArgument */

            $newArguments[$key] = $this->filterArgument(
                $oldArgument,
                $parameters,
                $decoratorServiceId,
                $newDecoratorServiceId
            );
        }

        $newDefinition->setArguments($newArguments);

        return $newDefinition;
    }

    /**
     * @param Reference|array|string $oldArgument
     *
     * @return Reference|array|string
     */
    private function filterArgument(
        $oldArgument,
        array $parameters,
        string $decoratorServiceId,
        string $newDecoratorServiceId
    ) {
        /** @var Reference|array|string $newArgument */
        $newArgument = $oldArgument;

        if (is_string($oldArgument)) {
            $newArgument = $this->filterStringArgument(
                $oldArgument,
                $parameters,
                $decoratorServiceId,
                $newDecoratorServiceId
            );
        }

        if ($oldArgument instanceof Reference) {
            /** @var string $newReferenceId */
            $newReferenceId = $this->filterStringArgument(
                $oldArgument->__toString(),
                $parameters,
                $decoratorServiceId,
                $newDecoratorServiceId
            );

            $newArgument = new Reference(
                $newReferenceId,
                $oldArgument->getInvalidBehavior()
            );
        }

        if (is_array($oldArgument)) {
            $newArgument = array();

            foreach ($oldArgument as $oldKey => $oldValue) {
                /** @var mixed $oldValue */

                /** @var int|string $newKey */
                $newKey = $this->filterArgument(
                    $oldKey,
                    $parameters,
                    $decoratorServiceId,
                    $newDecoratorServiceId
                );

                /** @var mixed $newValue */
                $newValue = $this->filterArgument(
                    $oldValue,
                    $parameters,
                    $decoratorServiceId,
                    $newDecoratorServiceId
                );

                $newArgument[$newKey] = $newValue;
            }
        }

        return $newArgument;
    }

    private function filterStringArgument(
        string $oldArgument,
        array $parameters,
        string $decoratorServiceId,
        string $newDecoratorServiceId
    ): string {
        /** @var string $newArgument */
        $newArgument = $oldArgument;

        if ($oldArgument === $decoratorServiceId . ".inner") {
            $newArgument = $newDecoratorServiceId . ".inner";

        } elseif (preg_match("/^\%([a-zA-Z0-9_]+)\%$/is", $oldArgument, $matches)) {
            /** @var string $parameterName */
            $parameterName = $matches[1];

            if (isset($parameters[$parameterName])) {
                $newArgument = $parameters[$parameterName];
            }
        }

        return $newArgument;
    }

}
