# Symfony UX TogglePassword

> [!WARNING]
> **Deprecated**: This package has been **deprecated** in 2.x and will be removed in the next major version.

To keep the same functionality in your Symfony application, follow these migration steps:

1. Remove the `symfony/ux-toggle-password` package from your project:
```bash
composer remove symfony/ux-toggle-password
```

2. Create the following files in your project:

> [!NOTE]
> These files are provided as a reference.
> You can customize them to fit your needs, and even simplify the implementation if you don't need all the features.

  - `src/Form/Extension/TogglePasswordTypeExtension.php`
```php
<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TogglePasswordTypeExtension extends AbstractTypeExtension
{
    public function __construct(private readonly ?TranslatorInterface $translator)
    {
    }

    public static function getExtendedTypes(): iterable
    {
        return [PasswordType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'toggle' => false,
            'hidden_label' => 'Hide',
            'visible_label' => 'Show',
            'hidden_icon' => 'Default',
            'visible_icon' => 'Default',
            'button_classes' => ['toggle-password-button'],
            'toggle_container_classes' => ['toggle-password-container'],
            'toggle_translation_domain' => null,
            'use_toggle_form_theme' => true,
        ]);

        $resolver->setNormalizer(
            'toggle_translation_domain',
            static fn (Options $options, $labelTranslationDomain) => $labelTranslationDomain ?? $options['translation_domain'],
        );

        $resolver->setAllowedTypes('toggle', ['bool']);
        $resolver->setAllowedTypes('hidden_label', ['string', TranslatableMessage::class, 'null']);
        $resolver->setAllowedTypes('visible_label', ['string', TranslatableMessage::class, 'null']);
        $resolver->setAllowedTypes('hidden_icon', ['string', 'null']);
        $resolver->setAllowedTypes('visible_icon', ['string', 'null']);
        $resolver->setAllowedTypes('button_classes', ['string[]']);
        $resolver->setAllowedTypes('toggle_container_classes', ['string[]']);
        $resolver->setAllowedTypes('toggle_translation_domain', ['string', 'bool', 'null']);
        $resolver->setAllowedTypes('use_toggle_form_theme', ['bool']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['toggle'] = $options['toggle'];

        if (!$options['toggle']) {
            return;
        }

        if ($options['use_toggle_form_theme']) {
            array_splice($view->vars['block_prefixes'], -1, 0, 'toggle_password');
        }

        $controllerName = 'toggle-password';
        $view->vars['attr']['data-controller'] = trim(\sprintf('%s %s', $view->vars['attr']['data-controller'] ?? '', $controllerName));

        if (false !== $options['toggle_translation_domain']) {
            $controllerValues['hidden-label'] = $this->translateLabel($options['hidden_label'], $options['toggle_translation_domain']);
            $controllerValues['visible-label'] = $this->translateLabel($options['visible_label'], $options['toggle_translation_domain']);
        } else {
            $controllerValues['hidden-label'] = $options['hidden_label'];
            $controllerValues['visible-label'] = $options['visible_label'];
        }

        $controllerValues['hidden-icon'] = $options['hidden_icon'];
        $controllerValues['visible-icon'] = $options['visible_icon'];
        $controllerValues['button-classes'] = json_encode($options['button_classes'], \JSON_THROW_ON_ERROR);

        foreach ($controllerValues as $name => $value) {
            $view->vars['attr'][\sprintf('data-%s-%s-value', $controllerName, $name)] = $value;
        }

        $view->vars['toggle_container_classes'] = $options['toggle_container_classes'];
    }

    private function translateLabel(string|TranslatableMessage|null $label, ?string $translationDomain): ?string
    {
        if (null === $this->translator || null === $label) {
            return $label;
        }

        if ($label instanceof TranslatableMessage) {
            return $label->trans($this->translator);
        }

        return $this->translator->trans($label, domain: $translationDomain);
    }
}
```
  - `template/form_theme.html.twig`, and follow the [How to work with form themes](https://symfony.com/doc/current/form/form_themes.html) documentation to register it.
```twig
{%- block toggle_password_widget -%}
    <div class="{{ toggle_container_classes|join(' ') }}">{{ block('password_widget') }}</div>
{%- endblock toggle_password_widget -%}
```
  - `assets/controllers/toggle_password_controller.js`
```javascript
import { Controller } from '@hotwired/stimulus';
import '../styles/toggle_password.css';

export default class extends Controller {
    static values = {
        visibleLabel: { type: String, default: 'Show' },
        visibleIcon: { type: String, default: 'Default' },
        hiddenLabel: { type: String, default: 'Hide' },
        hiddenIcon: { type: String, default: 'Default' },
        buttonClasses: Array,
    };

    isDisplayed = false;
    visibleIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="toggle-password-icon" viewBox="0 0 20 20" fill="currentColor">
<path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
<path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
</svg>`;
    hiddenIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="toggle-password-icon" viewBox="0 0 20 20" fill="currentColor">
<path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
<path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
</svg>`;

    connect() {
        if (this.visibleIconValue !== 'Default') {
            this.visibleIcon = this.visibleIconValue;
        }

        if (this.hiddenIconValue !== 'Default') {
            this.hiddenIcon = this.hiddenIconValue;
        }

        const button = this.createButton();

        this.element.insertAdjacentElement('afterend', button);
        this.dispatchEvent('connect', { element: this.element, button });
    }

    /**
     * @returns {HTMLButtonElement}
     */
    createButton() {
        const button = document.createElement('button');
        button.type = 'button';
        button.classList.add(...this.buttonClassesValue);
        button.setAttribute('tabindex', '-1');
        button.addEventListener('click', this.toggle.bind(this));
        button.innerHTML = `${this.visibleIcon} ${this.visibleLabelValue}`;
        return button;
    }

    /**
     * Toggle input type between "text" or "password" and update label accordingly
     */
    toggle(event) {
        this.isDisplayed = !this.isDisplayed;
        const toggleButtonElement = event.currentTarget;
        toggleButtonElement.innerHTML = this.isDisplayed
            ? `${this.hiddenIcon} ${this.hiddenLabelValue}`
            : `${this.visibleIcon} ${this.visibleLabelValue}`;
        this.element.setAttribute('type', this.isDisplayed ? 'text' : 'password');
        this.dispatchEvent(this.isDisplayed ? 'show' : 'hide', { element: this.element, button: toggleButtonElement });
    }

    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'toggle-password' });
    }
}
```
  - `assets/styles/toggle_password.css`
```css
.toggle-password-container {
    position: relative;
}
.toggle-password-icon {
    height: 1rem;
    width: 1rem;
}
.toggle-password-button {
    align-items: center;
    background-color: transparent;
    border: none;
    column-gap: 0.25rem;
    display: flex;
    flex-direction: row;
    font-size: 0.875rem;
    justify-items: center;
    height: 1rem;
    line-height: 1.25rem;
    position: absolute;
    right: 0.5rem;
    top: -1.25rem;
}
```

You're done!

---

Symfony UX TogglePassword is a Symfony bundle providing visibility toggle for password inputs
in Symfony Forms. It is part of [the Symfony UX initiative](https://ux.symfony.com/).

It allows visitors to switch the type of password field to text and vice versa.

**This repository is a READ-ONLY sub-tree split**. See
https://github.com/symfony/ux to create issues or submit pull requests.

## Sponsor

The Symfony UX packages are [backed][1] by [Mercure.rocks][2].

Create real-time experiences in minutes! Mercure.rocks provides a realtime API service
that is tightly integrated with Symfony: create UIs that update in live with UX Turbo,
send notifications with the Notifier component, expose async APIs with API Platform and
create low level stuffs with the Mercure component. We maintain and scale the complex
infrastructure for you!

Help Symfony by [sponsoring][3] its development!

## Resources

-   [Documentation](https://symfony.com/bundles/ux-toggle-password/current/index.html)
-   [Report issues](https://github.com/symfony/ux/issues) and
    [send Pull Requests](https://github.com/symfony/ux/pulls)
    in the [main Symfony UX repository](https://github.com/symfony/ux)

[1]: https://symfony.com/backers
[2]: https://mercure.rocks
[3]: https://symfony.com/sponsor
