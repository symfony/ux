---
title: Migrating to Turbo
description: Write your Javascript in a Turbo friendly way
image: images/cookbook/component-architecture.png
tags: 
    - JavaScript
    - Turbo
---

## Introduction

Turbo Drive is a fantastic tool. With just a few lines of code, you can transform your entire application into a real [Single Page Application (SPA)](https://developer.mozilla.org/docs/Glossary/SPA).

If you are migrating your application to Turbo Drive, everything may already work without any additional effort. However, there is one thing you should pay attention to: your JavaScript.

Turbo Drive requires you to write JavaScript in a specific way. The good news is that this approach is not unique to Turbo Drive. It involves writing better JavaScript that only needs to be loaded and executed once, and then continues to work seamlessly even when new content is loaded onto the page.

Let's illustrate this concept.

## Listen to page load

If you have the following code:

```javascript
// app.js

console.log('hello');

window.addEventListener('load', () => {
    console.log('load');
});
```

On the first load, everything works fine. 
However, if you reload the page or navigate to another page, the script will not be re-executed, and the log will not print in the console again. This might seem a bit unsettling at first, but it's actually desirable. 
By not re-executing our JavaScript, we significantly improve the performance of our pages.

## Javascript in your body

You might be tempted to add some JavaScript directly into your body like this:

```html
<body>
    ...
   <script>
        console.log('load !');
     </script>
 </body>
```

This will work initially—your log will appear in the console, and even if you refresh the page or navigate to another page, Turbo will re-execute the script. However, this is something we want to avoid with Turbo. We don't want to execute the same code repeatedly.

Moreover, you can encounter strange behaviors due to the Turbo cache. Your JavaScript might end up being executed twice or more. For example, consider the following script:

```html
<script>
    document.addEventListener('click', () => {
        console.log('body clicked!');
    })
</script>
```

With Turbo, the script is re-executed, but the page is not reloaded. Each time you change pages, you will add more event listeners to the document. So, if you've changed pages five times, clicking on the document will trigger the event listener five times.

## Javascript the right way

The best way to write your JavaScript with Turbo is to use Stimulus!

Let's say we want to implement a click counter somewhere in our app. Here’s how you can do it:

```javascript
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['count'];
    counter = 0;

    initialize() {
        this.counter = 0;
    }

    count() {
        this.counter++;

        this.countTarget.textContent = this.counter;
    }
}
```

With this setup, we have our click counter. More importantly, we have code that only needs to be loaded and executed once. This approach makes our JavaScript faster, easier to maintain, and fully compatible with Turbo.