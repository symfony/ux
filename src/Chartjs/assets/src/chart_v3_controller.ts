/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import AbstractChartController from './abstract_controller';
import Chart from 'chart.js/auto';
import { ChartConfiguration, ChartItem } from 'chart.js';

export default class extends AbstractChartController {
    createChart(canvasContext: ChartItem, payload: ChartConfiguration): any {
        return new Chart(canvasContext, payload);
    }
}
