---
title: Component Architecture
description: Rules and patterns for working with components
image: images/cookbook/component-architecture.png
tags: 
    - JavaScript
    - Symfony
author: Mathéo Daninos
published_at: '2024-08-02'
---

## Introduction

In Symfony UX, there are two packages: [TwigComponents](https://symfony.com/bundles/ux-twig-component/current/index.html) and [LiveComponent](https://symfony.com/bundles/ux-live-component/current/index.html).
These packages allow you to create reusable components in your Symfony application.
However, component architecture is not exclusive to Symfony; it's a design pattern that can be applied to any programming language or framework.
The JavaScript world has implemented this architecture for a long time, across many frameworks like React, Vue, or Svelte.
A set of rules and patterns has already been defined for working with components. This is why Symfony UX tries to adhere closely to these rules.
Let's explore what these rules are!

## 4 Rules

### Composition

A page is no longer just a page but rather a collection of small, reusable components.
These components can be assembled to form a page. For example, there could be a component for the title and another for the training list.
The training list component could even be composed of smaller components, such as a training card component.
The goal is to create the most atomic and reusable components possible.

***How does it work in Symfony?***

In Symfony, you can have an `Alert` component, for example, with the following template:

```twig
<div class="alert alert-{{ type }}">
    <twig:Icon name="{{ icon }}" />
    {{ message }}
</div>
```

So here you can see we have an `Alert` component that itself uses an Icon component.
Or you can compose with the following syntax:

```twig
<twig:Card>
    <twig:Icon name="info"/>
    <twig:Button>
        <twig:Icon name="close" />
    </twig:Button>
</twig:Card>
```

So here we have a `Card` component, and we provide the content of this component with two other components.

### Independence

This is a really important rule and not an obvious one. Your component should live in its own context; it
should not be aware of the rest of the page. You should be able to take a component from one page to another, and it should work exactly the same.
This rule makes your component truly reusable.

***How does it work in Symfony?***

Symfony keeps the context of the page within the context of your component. So it is your own responsibility to follow these rules.
Note that if there are conflicts between a variable from the context page and your component, your component context overrides the page context.

### Props

Our component must remain independent, but we can customize its props.
For example, consider a button component. You want your component to look the same on every page, with the only change being the label. 
To do this, you can declare a `label` prop in your button component.
When you use your button component, you can pass the label you want as a prop. The component will take this prop at initialization and keep it throughout its lifecycle.

***How does it work in Symfony?***

Let's take the example of the `Alert` component as an [anonymous component](https://symfony.com/bundles/ux-twig-component/current/index.html#anonymous-components).
We have the following template:

```twig
{% props type, icon, message %}

<div class="alert alert-{{ type }}">
    <twig:Icon name="{{ icon }}" />
    {{ message }}
</div>
```

Just like that, we define three props for our `Alert` component. We can now use it like this:

```twig
<twig:Alert type="success" icon="check" message="Your account has been created." />
```

If your component is not anonymous but a class component, you can define props by adding properties to your class.

```php
#[AsTwigComponent]
class Alert
{
    public string $type;
    public string $icon;
    public string $message;
}
```

There is something important to note with props: They should only flow in one direction, from parent to child. Props should never go up. **If your child needs to change something in the parent, you should use events.**

### State

A state is pretty much like a prop, but the main difference is that a state can 
change during the life of the component. Let's take the example of a button component.
You can have a `loading` state that can be `true` or `false`. When the button is clicked
the `loading` state can be set to `true`, and the button can display a loader instead of the label.
When the loading is done, the `loading` state can be set to `false`, and the button can display the label again.

***How does it work in Symfony?***

In Symfony, you have two different approaches to handle state. The first is to use Stimulus directly in your component. We recommend setting a Stimulus controller at the root of your component.

```twig
{% props label %}

<button data-controller="button" data-button-label-value="{{ label }}">
    {{ label }}
</button>
```

Then, you can define your controller like this:

```js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { label: String };

    connect() {
        this.element.textContent = this.labelValue;
    }

    loading() {
        this.element.textContent = 'Loading...';
    }
}
```

The second approach is to use the [LiveComponent](https://symfony.com/bundles/ux-live-component/current/index.html) package.
How to choose between the two? If your component doesn't need any backend logic 
for its state, keep it simple and use the Stimulus approach. But if you need to handle
backend logic for your state, use LiveComponent.
With LiveComponent, a live prop is a state. So if you want to store the number of clicks on a button you can do
so with the following component:

```php
<?php

#[AsLiveComponent]
class Button
{
    use DefaultActionTrait;
    
    #[LiveProp]
    public int $clicks = 0;

    #[LiveAction]
    public function increment(): void
    {
        $this->clicks++;

        $this->save();
    }
}
```

## Conclusion

Even in Symfony, you can use component architecture.
Following these rules helps your front-end developers work on a codebase they are familiar with since these rules are 
already widely used in the JavaScript world.
