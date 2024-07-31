import { Controller } from '@hotwired/stimulus';

enum PasswordStrength {
    STRENGTH_VERY_WEAK = 0,
    STRENGTH_WEAK = 1,
    STRENGTH_MEDIUM = 2,
    STRENGTH_STRONG = 3,
    STRENGTH_VERY_STRONG = 4,
}

export default class extends Controller {
    static values = {
        veryWeakMessage: { type: String, default: 'The password strength is too weak.' },
        weakMessage: { type: String, default: 'The password strength is weak.' },
        mediumMessage: { type: String, default: 'The password strength is medium.' },
        strongMessage: { type: String, default: 'The password strength is strong.' },
        veryStrongMessage: { type: String, default: 'The password strength is very strong.' },
    };
    static targets: string[] = ['score', 'message', 'meter'];

    declare readonly veryWeakMessageValue: string;
    declare readonly weakMessageValue: string;
    declare readonly mediumMessageValue: string;
    declare readonly strongMessageValue: string;
    declare readonly veryStrongMessageValue: string;
    declare readonly scoreTargets: HTMLElement[];
    declare readonly messageTargets: HTMLElement[];
    declare readonly meterTargets: HTMLElement[];

    connect() {
        this.dispatchEvent('connect', {});
    }

    private dispatchEvent(name: string, payload: any) {
        this.dispatch(name, { detail: payload, prefix: 'password-strength' });
    }

    public estimatePasswordStrength(event: InputEvent): void {
        const password = event.target?.value || '';
        const length = password.length;
        const result = {
            score: PasswordStrength.STRENGTH_VERY_WEAK,
            message: this.veryWeakMessageValue,
        };

        if (length !== 0) {
            const charCount = this.countChars(password);
            let control = 0;
            let digit = 0;
            let upper = 0;
            let lower = 0;
            let symbol = 0;
            let other = 0;

            for (const charCodeStr in charCount) {
                const charCode = parseInt(charCodeStr);
                const count = charCount[charCode];

                switch (true) {
                    case charCode < 32 || charCode === 127:
                        control += count * 33;
                        break;
                    case charCode >= 48 && charCode <= 57:
                        digit += count * 10;
                        break;
                    case charCode >= 65 && charCode <= 90:
                        upper += count * 26;
                        break;
                    case charCode >= 97 && charCode <= 122:
                        lower += count * 26;
                        break;
                    case charCode >= 128:
                        other += count * 128;
                        break;
                    default:
                        symbol += count * 33;
                }
            }

            const chars = Object.keys(charCount).length;
            const pool = lower + upper + digit + symbol + control + other;
            const entropy = chars * Math.log2(pool) + (length - chars) * Math.log2(chars);

            switch (true) {
                case entropy >= 120:
                    result.score = PasswordStrength.STRENGTH_VERY_STRONG;
                    result.message = this.veryStrongMessageValue;
                    break;
                case entropy >= 100:
                    result.score = PasswordStrength.STRENGTH_STRONG;
                    result.message = this.strongMessageValue;
                    break;
                case entropy >= 80:
                    result.score = PasswordStrength.STRENGTH_MEDIUM;
                    result.message = this.mediumMessageValue;
                    break;
                case entropy >= 60:
                    result.score = PasswordStrength.STRENGTH_WEAK;
                    result.message = this.weakMessageValue;
                    break;
                default:
                    result.score = PasswordStrength.STRENGTH_VERY_WEAK;
                    result.message = this.veryWeakMessageValue;
            }
        }

        this.messageTargets.forEach((element: HTMLElement) => {
            element.innerHTML = result.message;
        });
        this.scoreTargets.forEach((element: HTMLElement) => {
            element.innerHTML = result.score.toString();
        });
        this.meterTargets.forEach((element: HTMLElement) => {
            element.setAttribute('data-password-strength-estimate', result.score.toString());
        });
        this.dispatch('estimate', { detail: result });
    }

    private countChars(password: string): { [key: number]: number } {
        const charCount: { [key: number]: number } = {};
        for (const char of password) {
            const charCode = char.charCodeAt(0);
            charCount[charCode] = (charCount[charCode] || 0) + 1;
        }
        return charCount;
    }
}
