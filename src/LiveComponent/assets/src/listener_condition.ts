/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Jexl } from 'jexl';

const jexl = new Jexl();

/**
 * Evaluates the condition of a #[LiveListener] on the client.
 *
 * The expression has access to two variables:
 *  - "event": the data that was emitted along with the event
 *  - "props": the current props of the component that declares the listener
 *
 * For example, a LiveListener declared as:
 *
 *     #[LiveListener('product_updated(event.id == props.product)')]
 *
 * will only trigger its action if, on the client, the emitted event's "id"
 * matches this component's "product" prop.
 *
 * A malformed expression is treated as "false" (the listener is skipped)
 * so a typo in a condition can never accidentally trigger every listener,
 * and the error is logged to help debugging.
 */
export function evaluateListenerCondition(condition: string, eventData: any, props: any): boolean {
    try {
        return !!jexl.evalSync(condition, { event: eventData, props });
    } catch (error) {
        console.error(`LiveComponent: could not evaluate LiveListener condition "${condition}".`, error);

        return false;
    }
}
