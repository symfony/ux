/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application, Controller as BaseController } from "@hotwired/stimulus";
import Controller from "@symfony/ux-collection/dist/controller.js";

const application = Application.start();
application.register("symfony--ux-collection--collection", Controller);
application.register("test", class test extends BaseController {
    connect() {
        console.log('Yolo', this.element);
    }
});
console.log('test app initialized');
