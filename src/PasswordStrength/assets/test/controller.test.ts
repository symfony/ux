/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application, Controller } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import user from '@testing-library/user-event';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import PasswordStrengthController from '../src/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('password-strength:connect', () => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('password-strength', PasswordStrengthController);
};

describe('PasswordStrengthController', () => {
    let container;

    beforeEach(() => {
        container = mountDOM(`
            <html>
                <head>
                    <title>Symfony UX</title>
                </head>
                <body>
                    <form
                         data-testid="form"
                          data-controller="check password-strength"
                          data-password-strength-very-weak-message-value="TOO WEAK!"
                          data-password-strength-weak-message-value="WEAK!"
                          data-password-strength-medium-message-value="Not so bad!"
                          data-password-strength-strong-message-value="STRONG!"
                          data-password-strength-very-strong-message-value="VERY STRONG!"
                      >
                        <input data-testid="input" data-action="password-strength#estimatePasswordStrength" type='password' />
                        <div>The score is: <span data-testid="score" data-password-strength-target="score"></span></div>
                        <div>The message is: <span data-testid="message" data-password-strength-target="message"></span></div>
                        <div data-testid="meter" data-password-strength-target="meter">Meter</div>
                    </form>
                </body>
            </html>
        `);
    });

    afterEach(() => {
        clearDOM();
    });


    it('connects on mount', async () => {
        //Given
        const form = getByTestId(container, 'form');
        const input = getByTestId(container, 'input');
        const score = getByTestId(container, 'score');
        const message = getByTestId(container, 'message');
        const meter = getByTestId(container, 'meter');

        //Precondition
        expect(form).not.toHaveClass('connected');
        expect(input.type).toBe('password');
        expect(score.innerHTML).toBe('');
        expect(message.innerHTML).toBe('');
        expect(meter).not.toHaveAttribute('data-password-strength-score');

        //When
        startStimulus();

        //Then
        await waitFor(() => expect(form).toHaveClass('connected'));
    });
    const cases = [
        ['password', '0', 'TOO WEAK!'],
        ['How-is-this', '1', 'WEAK!'],
        ['Reasonable-pwd', '3', 'STRONG!'],
        ['This 1s a very g00d Pa55word! ;-)', '4', 'VERY STRONG!'],
        ['pudding-smack-👌🏼-fox-😎', '4', 'VERY STRONG!'],
    ];

    test.each(cases)(
        'given the password "%p" as argument, returns a score of %p and the message "%p"',
        async  (inputValue, expectedScore, expectedMessage) => {
            //Given
            const input = getByTestId(container, 'input');
            const score = getByTestId(container, 'score');
            const message = getByTestId(container, 'message');
            const meter = getByTestId(container, 'meter');
            startStimulus();

            //When
            user.type(input, inputValue);

            //User event
            await waitFor(() => expect(input).toHaveValue(inputValue));
            await waitFor(() => expect(score.innerHTML).toBe(expectedScore));
            await waitFor(() => expect(message.innerHTML).toBe(expectedMessage));
            await waitFor(() => expect(meter).toHaveAttribute('data-password-strength-estimate', expectedScore));
        }
    );
});
