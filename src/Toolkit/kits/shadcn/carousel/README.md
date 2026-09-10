# Carousel

A carousel with motion and swipe, driven by a Stimulus controller.

```twig {"preview":true,"height":"320px"}
<twig:Carousel class="mx-auto w-full max-w-[12rem] sm:max-w-xs">
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

To set the size of the slides, use a `basis-*` utility on the `Carousel:Item`.

```twig {"preview":true,"height":"260px"}
<twig:Carousel align="start" class="mx-auto w-full max-w-[12rem] sm:max-w-xs md:max-w-sm">
    <twig:Carousel:Content>
        {% for i in 1..5 %}
            <twig:Carousel:Item class="basis-1/2 lg:basis-1/3">
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

To set the spacing between the slides, use a `ps-*` utility on the `Carousel:Item` and the matching negative `-ms-*` on the `Carousel:Content`.

```twig {"preview":true,"height":"260px"}
<twig:Carousel class="mx-auto w-full max-w-[12rem] sm:max-w-xs md:max-w-sm">
    <twig:Carousel:Content class="-ms-1">
        {% for i in 1..5 %}
            <twig:Carousel:Item class="basis-1/2 ps-1 lg:basis-1/3">
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

### Multiple Items

```twig {"preview":true,"height":"260px"}
<twig:Carousel align="start" class="mx-auto max-w-xs sm:max-w-sm">
    <twig:Carousel:Content>
        {% for i in 1..5 %}
            <twig:Carousel:Item class="sm:basis-1/2 lg:basis-1/3">
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
    <twig:Carousel:Previous class="hidden sm:inline-flex" />
    <twig:Carousel:Next class="hidden sm:inline-flex" />
</twig:Carousel>
```

### Orientation

Use the `orientation` prop to set the axis the slides scroll along. A vertical carousel needs an explicit height on the `Carousel:Content`.

```twig {"preview":true,"height":"420px"}
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
```

### Options

Use the `align` prop to choose where the selected slide rests inside the viewport, and the `loop` prop to wrap around after the last slide.

```twig {"preview":true,"height":"320px"}
<twig:Carousel align="start" loop class="mx-auto w-full max-w-[12rem] sm:max-w-xs">
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

### API

The carousel dispatches a `carousel:select` event whenever the selected slide changes, carrying the slide `index` and the slide `count` in its detail. The `carousel-display` controller shipped with this recipe listens to it to render the current position.

```twig {"preview":true,"height":"360px"}
<div class="mx-auto w-full max-w-[10rem] sm:max-w-xs" data-controller="carousel-display" data-action="carousel:select->carousel-display#update">
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

Use the `autoplay` prop to advance the slides automatically, with a delay in milliseconds. Autoplay pauses while the carousel is hovered or focused, and restarts from a full delay after any interaction.

```twig {"preview":true,"height":"320px"}
<twig:Carousel autoplay="2000" class="mx-auto w-full max-w-[10rem] sm:max-w-xs">
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

```twig {"preview":true,"height":"620px"}
{% set arabicNumerals = ['١', '٢', '٣', '٤', '٥'] %}
<div class="flex flex-col items-center gap-12">
    {# Arabic #}
    <twig:Carousel dir="rtl" class="w-full max-w-[12rem] sm:max-w-xs">
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
    <twig:Carousel dir="rtl" class="w-full max-w-[12rem] sm:max-w-xs">
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
