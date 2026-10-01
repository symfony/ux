<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine\Preset;

use Symfony\UX\Css\Engine\JsValue;
use Symfony\UX\Css\Engine\TransformArgs;
use Symfony\UX\Css\Engine\Utilities;

/**
 * Port of the utility transforms of Panda's base preset (packages/preset-base/src/utilities).
 *
 * The color-mix transforms are not listed: the preset config names the property they mix.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class BaseTransforms
{
    private const SIBLINGS = '& > :not([hidden]) ~ :not([hidden])';
    private const GRADIENT_STOPS = 'var(--gradient-via-stops, var(--gradient-position), var(--gradient-from) var(--gradient-from-position), var(--gradient-to) var(--gradient-to-position))';
    private const GRADIENT_VIA_STOPS = 'var(--gradient-position), var(--gradient-from) var(--gradient-from-position), var(--gradient-via) var(--gradient-via-position), var(--gradient-to) var(--gradient-to-position)';
    private const LINEAR_GRADIENT_DIRECTIONS = [
        'to-t' => 'to top',
        'to-tr' => 'to top right',
        'to-r' => 'to right',
        'to-br' => 'to bottom right',
        'to-b' => 'to bottom',
        'to-bl' => 'to bottom left',
        'to-l' => 'to left',
        'to-tl' => 'to top left',
    ];
    private const TRANSITIONS = [
        'all' => 'all',
        'common' => 'color, background-color, border-color, outline-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter',
        'size' => 'width, height, min-width, max-width, min-height, max-height',
        'position' => 'left, right, top, bottom, inset, inset-inline, inset-block',
        'background' => 'background, background-color, background-image, background-position',
        'colors' => 'color, background-color, border-color, outline-color, text-decoration-color, fill, stroke',
        'opacity' => 'opacity',
        'shadow' => 'box-shadow',
        'transform' => 'transform',
    ];
    private const SR_ONLY = [
        'true' => [
            'position' => 'absolute',
            'width' => '1px',
            'height' => '1px',
            'padding' => '0',
            'margin' => '-1px',
            'overflow' => 'hidden',
            'clip' => 'rect(0, 0, 0, 0)',
            'whiteSpace' => 'nowrap',
            'borderWidth' => '0',
        ],
        'false' => [
            'position' => 'static',
            'width' => 'auto',
            'height' => 'auto',
            'padding' => '0',
            'margin' => '0',
            'overflow' => 'visible',
            'clip' => 'auto',
            'whiteSpace' => 'normal',
        ],
    ];
    private const CORNERS_BY_SIDE = [
        'Top' => ['TopLeft', 'TopRight'],
        'Right' => ['TopRight', 'BottomRight'],
        'Bottom' => ['BottomLeft', 'BottomRight'],
        'Left' => ['TopLeft', 'BottomLeft'],
        'Start' => ['StartStart', 'EndStart'],
        'End' => ['StartEnd', 'EndEnd'],
    ];
    private const FILTERS = ['brightness', 'contrast', 'grayscale', 'hueRotate', 'invert', 'saturate', 'sepia', 'blur'];
    private const BACKDROP_FILTERS = [
        'Blur',
        'Brightness',
        'Contrast',
        'Grayscale',
        'HueRotate',
        'Invert',
        'Saturate',
        'Sepia',
    ];
    private const TRANSFORM_PROPERTIES = [
        'rotateX',
        'rotateY',
        'rotateZ',
        'scaleX',
        'scaleY',
        'translateX',
        'translateY',
        'translateZ',
    ];
    private const WEBKIT_PREFIXED_PROPERTIES = [
        'boxDecorationBreak',
        'backgroundClip',
        'appearance',
        'backfaceVisibility',
        'clipPath',
        'hyphens',
        'mask',
        'maskImage',
        'maskSize',
        'textSizeAdjust',
    ];

    /**
     * @return array<string, \Closure(string|int|float|bool, TransformArgs): ?array<string, mixed>>
     */
    public static function all(): array
    {
        $linearGradient = static fn ($value, TransformArgs $args): array => self::linearGradient($value, $args);

        $transforms = [
            'float' => static fn ($value): array => match ($value) {
                'start' => ['float' => 'left', '[dir="rtl"] &' => ['float' => 'right']],
                'end' => ['float' => 'right', '[dir="rtl"] &' => ['float' => 'left']],
                default => ['float' => $value],
            },
            'hideFrom' => static function ($value, TransformArgs $args): array {
                $breakpoint = JsValue::toString($args->raw);
                if ($args->hasToken('breakpoints.'.$breakpoint)) {
                    $query = '@breakpoint '.$breakpoint;
                } else {
                    $query = '@media screen and (min-width: '.JsValue::toString($value).')';
                }

                return [$query => ['display' => 'none']];
            },
            'hideBelow' => static function ($value, TransformArgs $args): array {
                $breakpoint = JsValue::toString($args->raw);
                if ($args->hasToken('breakpoints.'.$breakpoint)) {
                    $query = '@breakpoint '.$breakpoint.'Down';
                } else {
                    $query = '@media screen and (max-width: '.JsValue::toString($value).')';
                }

                return [$query => ['display' => 'none']];
            },
            'spaceX' => static fn ($value): array => [
                self::SIBLINGS => ['marginInlineStart' => $value, 'marginInlineEnd' => '0px'],
            ],
            'spaceY' => static fn ($value): array => [
                self::SIBLINGS => ['marginTop' => $value, 'marginBottom' => '0px'],
            ],
            'outline' => static fn ($value): array => 'none' === $value
                ? ['outline' => '2px solid transparent', 'outlineOffset' => '2px']
                : ['outline' => $value],
            'focusRing' => static fn ($value): array => self::focusRing('&:is(:focus, [data-focus])', $value),
            'focusVisibleRing' => static fn ($value): array => self::focusRing(
                '&:is(:focus-visible, [data-focus-visible])',
                $value,
            ),
            'focusRingOffset' => static fn ($value): array => ['--focus-ring-offset' => $value],
            'focusRingWidth' => static fn ($value): array => ['--focus-ring-width' => $value],
            'focusRingStyle' => static fn ($value): array => ['--focus-ring-style' => $value],
            'divideX' => static fn ($value): array => [
                self::SIBLINGS => ['borderInlineStartWidth' => $value, 'borderInlineEndWidth' => '0px'],
            ],
            'divideY' => static fn ($value): array => [
                self::SIBLINGS => ['borderTopWidth' => $value, 'borderBottomWidth' => '0px'],
            ],
            'divideColor' => static fn ($value, TransformArgs $args): array => [
                self::SIBLINGS => Utilities::colorMixTransform('borderColor')($value, $args),
            ],
            'divideStyle' => static fn ($value): array => [self::SIBLINGS => ['borderStyle' => $value]],
            'boxSize' => static fn ($value): array => ['width' => $value, 'height' => $value],
            'fontSmoothing' => static fn ($value): array => ['WebkitFontSmoothing' => $value],
            'textWrap' => static fn ($value): array => ['textWrap' => $value],
            'truncate' => static fn ($value): array => JsValue::isTruthy($value)
                ? ['overflow' => 'hidden', 'textOverflow' => 'ellipsis', 'whiteSpace' => 'nowrap']
                : [],
            'lineClamp' => static function ($value): array {
                if ('none' === $value) {
                    return ['WebkitLineClamp' => 'unset'];
                }

                return [
                    'overflow' => 'hidden',
                    'display' => '-webkit-box',
                    'WebkitLineClamp' => $value,
                    'WebkitBoxOrient' => 'vertical',
                ];
            },
            'backgroundGradient' => $linearGradient,
            'backgroundLinear' => $linearGradient,
            'backgroundRadial' => static function ($value, TransformArgs $args): array {
                if ($token = self::gradientToken($args)) {
                    return ['backgroundImage' => $token];
                }

                return [
                    '--gradient-stops' => self::GRADIENT_STOPS,
                    '--gradient-position' => $value,
                    'backgroundImage' => 'radial-gradient(var(--gradient-stops,'.JsValue::toString($value).'))',
                ];
            },
            'backgroundConic' => static fn ($value): array => [
                '--gradient-stops' => self::GRADIENT_STOPS,
                '--gradient-position' => $value,
                'backgroundImage' => 'conic-gradient(var(--gradient-stops))',
            ],
            'textGradient' => static fn ($value, TransformArgs $args): array => [
                ...self::linearGradient($value, $args),
                'WebkitBackgroundClip' => 'text',
                'color' => 'transparent',
            ],
            'gradientFromPosition' => static fn ($value): array => ['--gradient-from-position' => $value],
            'gradientToPosition' => static fn ($value): array => ['--gradient-to-position' => $value],
            'gradientVia' => static fn ($value, TransformArgs $args): array => [
                ...Utilities::colorMixTransform('--gradient-via')($value, $args),
                '--gradient-stops' => 'var(--gradient-via-stops)',
                '--gradient-via-stops' => self::GRADIENT_VIA_STOPS,
            ],
            'gradientViaPosition' => static fn ($value): array => ['--gradient-via-position' => $value],
            'dropShadow' => static fn ($value): array => ['--drop-shadow' => $value],
            'backdropFilter' => static fn ($value): array => [
                'WebkitBackdropFilter' => $value,
                'backdropFilter' => $value,
            ],
            'backdropOpacity' => static fn ($value): array => ['--backdrop-opacity' => $value],
            'borderSpacingX' => static fn ($value): array => ['--border-spacing-x' => $value],
            'borderSpacingY' => static fn ($value): array => ['--border-spacing-y' => $value],
            'transitionTimingFunction' => static fn ($value): array => [
                '--transition-easing' => $value,
                'transitionTimingFunction' => $value,
            ],
            'transitionDuration' => static fn ($value): array => [
                '--transition-duration' => $value,
                'transitionDuration' => $value,
            ],
            'transitionProperty' => static fn ($value): array => [
                '--transition-prop' => $value,
                'transitionProperty' => $value,
            ],
            'transition' => static function ($value): array {
                if (\is_string($value) && isset(self::TRANSITIONS[$value])) {
                    return [
                        'transitionProperty' => 'var(--transition-prop, '.self::TRANSITIONS[$value].')',
                        'transitionTimingFunction' => 'var(--transition-easing, cubic-bezier(0.4, 0, 0.2, 1))',
                        'transitionDuration' => 'var(--transition-duration, 150ms)',
                    ];
                }

                return ['transition' => $value];
            },
            'scrollbar' => static fn ($value): ?array => match ($value) {
                'visible' => [
                    'msOverflowStyle' => 'auto',
                    'scrollbarWidth' => 'auto',
                    '&::-webkit-scrollbar' => ['display' => 'block'],
                ],
                'hidden' => [
                    'msOverflowStyle' => 'none',
                    'scrollbarWidth' => 'none',
                    '&::-webkit-scrollbar' => ['display' => 'none'],
                ],
                default => null,
            },
            'scrollSnapStrictness' => static fn ($value): array => ['--scroll-snap-strictness' => $value],
            'srOnly' => static fn ($value): array => self::SR_ONLY[JsValue::toString($value)] ?? [],
            'debug' => static fn ($value): array => JsValue::isTruthy($value)
                ? ['outline' => '1px solid blue !important', '&>*' => ['outline' => '1px solid red !important']]
                : [],
        ];

        foreach (self::CORNERS_BY_SIDE as $side => [$first, $second]) {
            $transforms['border'.$side.'Radius'] = static fn ($value): array => [
                'border'.$first.'Radius' => $value,
                'border'.$second.'Radius' => $value,
            ];
        }
        foreach (self::FILTERS as $filter) {
            $transforms[$filter] = self::filter('--', $filter);
        }
        foreach (self::BACKDROP_FILTERS as $filter) {
            $transforms['backdrop'.$filter] = self::filter('--backdrop-', lcfirst($filter));
        }
        foreach (self::TRANSFORM_PROPERTIES as $property) {
            $variable = '--'.strtolower(preg_replace('/[A-Z]/', '-$0', $property));
            $transforms[$property] = static fn ($value): array => [$variable => $value];
        }
        $transforms['userSelect'] = static fn ($value): array => ['WebkitUserSelect' => $value, 'userSelect' => $value];
        foreach (self::WEBKIT_PREFIXED_PROPERTIES as $property) {
            $transforms[$property] = static fn ($value): array => [
                $property => $value,
                'Webkit'.ucfirst($property) => $value,
            ];
        }

        return $transforms;
    }

    private static function filter(string $prefix, string $function): \Closure
    {
        $name = strtolower(preg_replace('/[A-Z]/', '-$0', $function));

        return static fn ($value): array => [$prefix.$name => $name.'('.JsValue::toString($value).')'];
    }

    /**
     * @return array<string, mixed>
     */
    private static function focusRing(string $selector, string|int|float|bool $value): array
    {
        $color = 'var(--focus-ring-color-prop, var(--global-color-focus-ring, #005FCC))';

        return match ($value) {
            'inside' => [
                '--focus-ring-color' => $color,
                $selector => [
                    'outlineOffset' => '0px',
                    'outlineWidth' => 'var(--focus-ring-width, 1px)',
                    'outlineColor' => 'var(--focus-ring-color)',
                    'outlineStyle' => 'var(--focus-ring-style, solid)',
                    'borderColor' => 'var(--focus-ring-color)',
                ],
            ],
            'outside' => [
                '--focus-ring-color' => $color,
                $selector => [
                    'outlineWidth' => 'var(--focus-ring-width, 2px)',
                    'outlineOffset' => 'var(--focus-ring-offset, 2px)',
                    'outlineStyle' => 'var(--focus-ring-style, solid)',
                    'outlineColor' => 'var(--focus-ring-color)',
                ],
            ],
            'mixed' => [
                '--focus-ring-color' => $color,
                $selector => [
                    'outlineOffset' => '0px',
                    'outlineWidth' => 'var(--focus-ring-width, 3px)',
                    'outlineStyle' => 'var(--focus-ring-style, solid)',
                    'outlineColor' => 'color-mix(in srgb, var(--focus-ring-color), transparent 60%)',
                    'borderColor' => 'var(--focus-ring-color)',
                ],
            ],
            'none' => ['--focus-ring-color' => $color, $selector => ['outline' => 'none']],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function linearGradient(string|int|float|bool $value, TransformArgs $args): array
    {
        if (null !== $token = self::gradientToken($args)) {
            return ['backgroundImage' => $token];
        }
        if (!\is_string($args->raw) || !isset(self::LINEAR_GRADIENT_DIRECTIONS[$args->raw])) {
            return ['backgroundImage' => $value];
        }

        return [
            '--gradient-stops' => self::GRADIENT_STOPS,
            '--gradient-position' => self::LINEAR_GRADIENT_DIRECTIONS[$args->raw],
            'backgroundImage' => 'linear-gradient(var(--gradient-stops))',
        ];
    }

    private static function gradientToken(TransformArgs $args): ?string
    {
        $token = $args->token('gradients.'.JsValue::toString($args->raw));

        return JsValue::isTruthy($token) ? $token : null;
    }
}
