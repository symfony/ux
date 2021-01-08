/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application } from "stimulus";
import TurboStreamController from "@symfony/ux-turbo/dist/turbo_stream_controller";

const application = Application.start();
application.register("turbo-stream", TurboStreamController);
