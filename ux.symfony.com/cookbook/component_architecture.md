---
title: Component architecture
description: Rules and pattern to work with components
image: images/cookbook/component-architecture.png
tags: 
    - javascript
    - symfony
---

## Introduction

In SymfonyUX exist two packages: [TwigComponents](https://symfony.com/bundles/ux-twig-component/current/index.html) and [LiveComponent](https://symfony.com/bundles/ux-live-component/current/index.html).
Those two packages allow you to create reusable components in your Symfony application.
But the component architecture is not exclusive to Symfony, it is a design pattern that can be applied to any programming language or framework.
And the js world already implement this architecture for long time, on many different frameworks like React, Vue, or Svelte.
So, a set of rules and pattern has already be defined to work with components. This is why SymfonyUX try to be as close as possible to those rules.
So let's see what are those rules!

## 4 Rules

### Composition

A page is no longer just a page, but rather a collection of small, reusable components. 
These components can be assembled to form a page. For example, there could be a component for the title and another for the training list. 
The training list component could even be composed of smaller components, such as a training card component. 
The goal is to create the most atomic, and reusable components possible.

#### How does it work into Symfony?

In Symfony you can have a component Alert for example with the following template:

```twig
<div class="alert alert-{{ type }}">
    <twig:Icon name="{{ icon }}" />
    {{ message }}
</div>
```

So here you can see we have an alert component that his himself use an Icon component.
Or you can make composition with the following syntax:

```twig
<twig:Card>
    <twig:CardHeader>
        <h2>My Card</h2>
    </twig:CardHeader>
    <twig:CardBody>
        <p>This is the content of my card.</p>
    </twig:CardBody>
</twig:Card>
```

So here we Card component, and we give to the content of this component mutliple other components.

### Independence

This is a really important rule, and not obvious. But your component should leave on his own context,
he should not be aware of the rest of the page. You should to talk one component into a page, to another and it should work exactly the same.
This rule make your component trully reusable.

***How does it work into Symfony?***

Symfony keep the context of the page into the context of your component. So this your own responsability to follow this rules.
But notice that if there are conflic between a variable from the context page and your component, your component context override the page context.

### Props

Our component must remain independent, but we can customize it props. 
Let's take the example of a button component. You have your component that look on every page the same,
the only change is the label. What you can do is to declare a prop `label` into your button component.
And so now when you want to use your button component, you can pass the label you want as props. The component gonna take
this props at his initialization and keep it all his life long.

***How does it work into Symfony?***

Let's take the example of the Alert component an [anonymous component](https://symfony.com/bundles/ux-twig-component/current/index.html#anonymous-components).
We have the following template:

```twig
{% props type, icon, message %}

<div class="alert alert-{{ type }}">
    <twig:Icon name="{{ icon }}" />
    {{ message }}
</div>
```

Just like that we define three props for our Alert component. And know we can use like this:

```twig
<twig:Alert type="success" icon="check" message="Your account has been created." />
```

If your component anonymous but a class component, you can simply define props
by adding property to your class.

```php
class Alert
{
    public string $type;
    public string $icon;
    public string $message;
}
```

There are something really important to notice with props. It's your props
should only go into one direction from the parent to child. But your props should never
go up. **If your child need to change something in the parent, you should use events**.

### State

A state is pretty much like a prop but the main difference is a state can 
change during the life of the component. Let's take the example of a button component.
You can have a state `loading` that can be `true` or `false`. When the button is clicked
the state `loading` can be set to `true` and the button can display a loader instead of the label.
And when the loading is done, the state `loading` can be set to `false` and the button can display the label again.

***How does it work into Symfony?***

In symfony you 2 different approach to handle state. The first one is to use stimulus directly
in to your component. What we recommend to do is to set a controller stimulus at the root of your component.

```twig
{% props label %}

<button data-controller="button" data-button-label="{{ label }}">
    {{ label }}
</button>
```

And then you can define your controller like this:

```js
import { Controller } from 'stimulus';

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
How to choose between the two? If your component don't need any backend logic 
for his state keep it simple and use stimulus approach. But if you need to handle
backend logic for your state, use LiveComponent.
With live component a live prop is a state. So if you want store the number of click on a button you can do
the following component:

```php
<?php

#[AsLiveComponent]
class Button
{
    #[LiveProp]
    public int $clicks = 0;

    public function increment()
    {
        $this->clicks++;

        $this->save();
    }
}
```

## Conclusion

Even in Symfony, you can use the component architecture.
Follow those rules help your front developers working on codebase
they are familiar with since those rules are already used in the JS world.
