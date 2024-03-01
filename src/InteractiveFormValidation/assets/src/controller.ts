/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict'

import { Controller } from '@hotwired/stimulus'

export type AlertStrategy = 'browser_native' | 'emit_event'
export const invalidFormAlertEventName = 'interactive-form-validation-invalid-alert'

/**
 * @author Mateusz Anders <anders_mateusz@outlook.com>
 */
export default class extends Controller {
    declare readonly msgValue: string
    declare readonly idValue?: string
    declare readonly withAlertValue: boolean
    declare readonly alertStrategyValue: AlertStrategy
    static values = {
        msg: String,
        id: String,
        withAlert: Boolean,
        alertStrategy: String,
    }

    executeBrowserStrategy = () => {
        alert(this.msgValue)
    }

    executeEmitStrategy = () => {
        document.dispatchEvent(new CustomEvent(invalidFormAlertEventName, { detail: this.msgValue }))
    }

    connect() {
        super.connect()

        if (this.withAlertValue) this.resolveStrategy()()

        if (this.idValue) {
            const el = document.getElementById(this.idValue)
            if (el instanceof HTMLElement) {
                el.focus({ preventScroll: true })
                el.scrollIntoView({ block: 'center', behavior: 'smooth' })
            }
        }
    }

    resolveStrategy(): (() => void) {
        if (this.alertStrategyValue === 'browser_native') {
            return this.executeBrowserStrategy
        } else {
            return this.executeEmitStrategy
        }
    }
}
