<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Twig\PropsNode;
use Twig\Environment;

final class ComponentPropertiesExtractor
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getComponentProperties(ComponentMetadata $medata)
    {
        if ($medata->isAnonymous()) {
            return $this->getAnonymousComponentProperties($medata);
        }

        return $this->getNonAnonymousComponentProperties($medata);
    }

    /**
     * @return array<string, string>
     */
    private function getNonAnonymousComponentProperties(ComponentMetadata $metadata): array
    {
        $properties = [];
        $reflectionClass = new \ReflectionClass($metadata->getClass());
        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();

            if ($metadata->isPublicPropsExposed() && $property->isPublic()) {
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                } else {
                    $typeName = (string) $type;
                }
                $value = $property->getDefaultValue();
                $propertyDisplay = $typeName.' $'.$propertyName.(null !== $value ? ' = '.json_encode(
                    $value
                ) : '');
                $properties[$property->name] = [
                    'name' => $propertyName,
                    'display' => $propertyDisplay,
                    'type' => $typeName,
                    'default' => $value,
                ];
            }

            foreach ($property->getAttributes(ExposeInTemplate::class) as $exposeAttribute) {
                /** @var ExposeInTemplate $attribute */
                $attribute = $exposeAttribute->newInstance();
                $properties[$property->name] = [
                    'name' => $attribute->name ?? $property->name,
                    'display' => $attribute->name ?? $property->name,
                    'type' => 'mixed',
                    'default' => null,
                ];
            }
        }

        return $properties;
    }

    /**
     * Extract properties from {% props %} tag in anonymous template.
     *
     * @return array<string, string>
     */
    private function getAnonymousComponentProperties(ComponentMetadata $metadata): array
    {
        $source = $this->twig->load($metadata->getTemplate())->getSourceContext();
        $tokenStream = $this->twig->tokenize($source);
        $moduleNode = $this->twig->parse($tokenStream);

        $propsNode = null;
        foreach ($moduleNode->getNode('body') as $bodyNode) {
            foreach ($bodyNode as $node) {
                if (PropsNode::class === $node::class) {
                    $propsNode = $node;
                    break 2;
                }
            }
        }
        if (!$propsNode instanceof PropsNode) {
            return [];
        }

        $propertyNames = $propsNode->getAttribute('names');
        $properties = array_combine($propertyNames, $propertyNames);
        foreach ($propertyNames as $propName) {
            if ($propsNode->hasNode($propName)
                && ($valueNode = $propsNode->getNode($propName))
                && $valueNode->hasAttribute('value')
            ) {
                $value = $valueNode->getAttribute('value');
                if (\is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } else {
                    $value = json_encode($value);
                }
                $display = $propName.' = '.$value;
                $properties[$propName] = [
                    'name' => $propName,
                    'display' => $display,
                    'type' => \is_bool($value) ? 'bool' : 'mixed',
                    'default' => $value,
                ];
            }
        }

        foreach ($properties as $propertyData) {
            if (\is_string($propertyData)) {
                $properties[$propertyData] = [
                    'name' => $propertyData,
                    'display' => $propertyData,
                    'type' => 'mixed',
                    'default' => null,
                ];
            }
        }

        return $properties;
    }
}
