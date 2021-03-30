/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application } from "stimulus";
import TurboStreamMercureController from "@symfony/mercure-ux-turbo/dist/turbo_stream_controller";

const application = Application.start();
application.register("symfony--mercure-ux-turbo--turbo-stream", TurboStreamMercureController);

console.log('test app initialized');
