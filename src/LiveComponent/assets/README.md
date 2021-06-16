# Live Stimulus

## Installation

Install the library:

```
composer require @symfony/ux-live-component
```

Re-install your assets:

```
yarn install --force
```

Now, register the new controller with Stimulus.

**Symfony:**

```diff
// assets/bootstrap.js
import { startStimulusApp } from '@symfony/stimulus-bridge';
// ...

+import LiveController from '@symfony/ux-live-component';
+app.register('live', LiveController);

+// import some minor CSS
+import '@symfony/ux-live-component/styles/live.css';
```

