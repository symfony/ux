/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application } from "stimulus";
import TurboStreamMercureController from "@symfony/ux-turbo/dist/turbo_stream_mercure_controller";

const application = Application.start();
application.register("symfony--ux-turbo--turbo-stream-mercure", TurboStreamMercureController);

console.log('test app initialized');
