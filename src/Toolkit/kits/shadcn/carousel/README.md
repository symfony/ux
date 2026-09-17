# Carousel

A carousel with motion and swipe, built on [Embla Carousel](https://www.embla-carousel.com/).

```twig {"preview":true,"height":"360px"}
<twig:Carousel class="mx-auto w-full max-w-[10rem] sm:max-w-[14rem]">
    <twig:Carousel:Content>
        {% for i in 1..5 %}
            <twig:Carousel:Item>
                <div class="p-1">
                    <twig:Card>
                        <twig:Card:Content class="flex aspect-square items-center justify-center p-6">
                            <span class="text-4xl font-semibold">{{ i }}</span>
                        </twig:Card:Content>
                    </twig:Card>
                </div>
            </twig:Carousel:Item>
        {% endfor %}
    </twig:Carousel:Content>
    <twig:Carousel:Previous />
    <twig:Carousel:Next />
</twig:Carousel>
```

The carousel is focusable and responds to arrow keys: `Left`/`Right` on a horizontal carousel, `Up`/`Down` on a vertical one. In RTL, the horizontal mapping is mirrored.

## Installation

::: installation

## Usage

```twig
<twig:Carousel orientation="horizontal | vertical" align="start | center | end" loop>
    <twig:Carousel:Content>
        <twig:Carousel:Item>...</twig:Carousel:Item>
        <twig:Carousel:Item>...</twig:Carousel:Item>
        <twig:Carousel:Item>...</twig:Carousel:Item>
    </twig:Carousel:Content>
    <twig:Carousel:Previous />
    <twig:Carousel:Next />
</twig:Carousel>
```

## Examples

### Sizes

Set slide size with a `basis-*` utility on `Carousel:Item`.

```twig {"preview":true,"height":"250px"}
<twig:Carousel align="start" class="mx-auto w-full max-w-[12rem] sm:max-w-xs md:max-w-sm">
    <twig:Carousel:Content>
        {% for i in 1..5 %}
            <twig:Carousel:Item class="basis-1/3">
                <div class="p-1">
                    <twig:Card>
                        <twig:Card:Content class="flex aspect-square items-center justify-center p-6">
                            <span class="text-3xl font-semibold">{{ i }}</span>
                        </twig:Card:Content>
                    </twig:Card>
                </div>
            </twig:Carousel:Item>
        {% endfor %}
    </twig:Carousel:Content>
    <twig:Carousel:Previous />
    <twig:Carousel:Next />
</twig:Carousel>
```

### Spacing

Set spacing between slides with a `ps-*` utility on `Carousel:Item`, matched by a negative `-ms-*` on `Carousel:Content`.

```twig {"preview":true,"height":"260px"}
<twig:Carousel class="mx-auto w-full max-w-[12rem] sm:max-w-xs md:max-w-sm">
    <twig:Carousel:Content class="-ms-1">
        {% for i in 1..5 %}
            <twig:Carousel:Item class="basis-1/3 ps-1">
                <div class="p-1">
                    <twig:Card>
                        <twig:Card:Content class="flex aspect-square items-center justify-center p-6">
                            <span class="text-2xl font-semibold">{{ i }}</span>
                        </twig:Card:Content>
                    </twig:Card>
                </div>
            </twig:Carousel:Item>
        {% endfor %}
    </twig:Carousel:Content>
    <twig:Carousel:Previous />
    <twig:Carousel:Next />
</twig:Carousel>
```

### Orientation

The `orientation` prop sets the axis the slides scroll along. A vertical carousel needs an explicit height on `Carousel:Content`.

```twig {"preview":true,"height":"490px"}
<div class="w-full py-16">
    <twig:Carousel orientation="vertical" align="start" class="mx-auto w-full max-w-xs">
        <twig:Carousel:Content class="-mt-1 h-[270px]">
            {% for i in 1..5 %}
                <twig:Carousel:Item class="basis-1/2 pt-1">
                    <div class="p-1">
                        <twig:Card>
                            <twig:Card:Content class="flex items-center justify-center p-6">
                                <span class="text-3xl font-semibold">{{ i }}</span>
                            </twig:Card:Content>
                        </twig:Card>
                    </div>
                </twig:Carousel:Item>
            {% endfor %}
        </twig:Carousel:Content>
        <twig:Carousel:Previous />
        <twig:Carousel:Next />
    </twig:Carousel>
</div>
```

### Options

Use the `align` prop to set where the selected slide rests inside the viewport, that's the only difference between the three carousels below. Each one also passes `containScroll: false` through `options`, which forwards any [Embla Carousel option](https://www.embla-carousel.com/api/options/): without it, Embla trims the empty space at both ends and every alignment lands on the same position.

```twig {"preview":true,"height":"630px"}
<div class="mx-auto flex w-full max-w-[10rem] flex-col gap-8 sm:max-w-xs">
    {% for alignment in ['start', 'center', 'end'] %}
        <div>
            <p class="mb-2 text-center text-sm text-muted-foreground">align="{{ alignment }}"</p>
            <twig:Carousel align="{{ alignment }}" :options="{containScroll: false}">
                <twig:Carousel:Content>
                    {% for i in 1..5 %}
                        <twig:Carousel:Item class="basis-1/3">
                            <div class="p-1">
                                <twig:Card>
                                    <twig:Card:Content class="flex aspect-square items-center justify-center p-2">
                                        <span class="text-xl font-semibold">{{ i }}</span>
                                    </twig:Card:Content>
                                </twig:Card>
                            </div>
                        </twig:Carousel:Item>
                    {% endfor %}
                </twig:Carousel:Content>
                <twig:Carousel:Previous />
                <twig:Carousel:Next />
            </twig:Carousel>
        </div>
    {% endfor %}
</div>
```

### Loop

The `loop` prop wraps around after the last slide, so `Carousel:Previous` stays enabled on the first one and `Carousel:Next` on the last.

```twig {"preview":true,"height":"250px"}
<twig:Carousel align="start" loop class="mx-auto w-full max-w-[12rem] sm:max-w-xs md:max-w-sm">
    <twig:Carousel:Content>
        {% for i in 1..5 %}
            <twig:Carousel:Item class="basis-1/3">
                <div class="p-1">
                    <twig:Card>
                        <twig:Card:Content class="flex aspect-square items-center justify-center p-6">
                            <span class="text-3xl font-semibold">{{ i }}</span>
                        </twig:Card:Content>
                    </twig:Card>
                </div>
            </twig:Carousel:Item>
        {% endfor %}
    </twig:Carousel:Content>
    <twig:Carousel:Previous />
    <twig:Carousel:Next />
</twig:Carousel>
```

### API

The carousel dispatches a `carousel:select` event on every slide change, with the slide `index` and `count` in its detail. The `carousel-display` controller shipped with this recipe uses it to show the current position.

```twig {"preview":true,"height":"390px"}
<div class="mx-auto w-full max-w-[10rem] sm:max-w-[14rem]" data-controller="carousel-display" data-action="carousel:select->carousel-display#update">
    <twig:Carousel>
        <twig:Carousel:Content>
            {% for i in 1..5 %}
                <twig:Carousel:Item>
                    <twig:Card class="m-px">
                        <twig:Card:Content class="flex aspect-square items-center justify-center p-6">
                            <span class="text-4xl font-semibold">{{ i }}</span>
                        </twig:Card:Content>
                    </twig:Card>
                </twig:Carousel:Item>
            {% endfor %}
        </twig:Carousel:Content>
        <twig:Carousel:Previous />
        <twig:Carousel:Next />
    </twig:Carousel>
    <div class="py-2 text-center text-sm text-muted-foreground" data-carousel-display-target="output">Slide 1 of 5</div>
</div>
```

### Autoplay

The `autoplay` prop takes a delay in milliseconds, `0` disables it. It is backed by Embla's autoplay plugin, configured to pause while the pointer is over the carousel and to resume after the previous/next buttons are clicked.

```twig {"preview":true,"height":"360px"}
<twig:Carousel autoplay="2000" loop class="mx-auto w-full max-w-[10rem] sm:max-w-[14rem]">
    <twig:Carousel:Content>
        {% for i in 1..5 %}
            <twig:Carousel:Item>
                <div class="p-1">
                    <twig:Card>
                        <twig:Card:Content class="flex aspect-square items-center justify-center p-6">
                            <span class="text-4xl font-semibold">{{ i }}</span>
                        </twig:Card:Content>
                    </twig:Card>
                </div>
            </twig:Carousel:Item>
        {% endfor %}
    </twig:Carousel:Content>
    <twig:Carousel:Previous />
    <twig:Carousel:Next />
</twig:Carousel>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"660px"}
{% set arabicNumerals = ['١', '٢', '٣', '٤', '٥'] %}
<div class="flex flex-col items-center gap-12">
    {# Arabic #}
    <twig:Carousel dir="rtl" class="w-full max-w-[10rem] sm:max-w-[14rem]">
        <twig:Carousel:Content>
            {% for i in 1..5 %}
                <twig:Carousel:Item>
                    <div class="p-1">
                        <twig:Card>
                            <twig:Card:Content class="flex aspect-square items-center justify-center p-6">
                                <span class="text-4xl font-semibold">{{ arabicNumerals[i - 1] }}</span>
                            </twig:Card:Content>
                        </twig:Card>
                    </div>
                </twig:Carousel:Item>
            {% endfor %}
        </twig:Carousel:Content>
        <twig:Carousel:Previous text="الشريحة السابقة" />
        <twig:Carousel:Next text="الشريحة التالية" />
    </twig:Carousel>

    {# Hebrew #}
    <twig:Carousel dir="rtl" class="w-full max-w-[10rem] sm:max-w-[14rem]">
        <twig:Carousel:Content>
            {% for i in 1..5 %}
                <twig:Carousel:Item>
                    <div class="p-1">
                        <twig:Card>
                            <twig:Card:Content class="flex aspect-square items-center justify-center p-6">
                                <span class="text-4xl font-semibold">{{ i }}</span>
                            </twig:Card:Content>
                        </twig:Card>
                    </div>
                </twig:Carousel:Item>
            {% endfor %}
        </twig:Carousel:Content>
        <twig:Carousel:Previous text="השקופית הקודמת" />
        <twig:Carousel:Next text="השקופית הבאה" />
    </twig:Carousel>
</div>
```

## API Reference

::: api-reference
