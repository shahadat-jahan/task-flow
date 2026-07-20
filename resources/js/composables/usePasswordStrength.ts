import type { ComputedRef, Ref } from 'vue';
import { computed } from 'vue';

export type UsePasswordStrengthReturn = {
    strength: ComputedRef<number>;
    strengthColors: string[];
    strengthLabels: string[];
};

export const strengthColors = ['#EF4444', '#F59E0B', '#FACC15', '#22C55E'];

export const strengthLabels = ['Weak', 'Fair', 'Good', 'Strong'];

export function usePasswordStrength(
    password: Ref<string>,
): UsePasswordStrengthReturn {
    const strength = computed(() => {
        const pw = password.value;

        if (!pw) {
            return 0;
        }

        let score = 0;

        if (pw.length >= 8) {
            score++;
        }

        if (pw.length >= 12) {
            score++;
        }

        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) {
            score++;
        }

        if (/\d/.test(pw)) {
            score++;
        }

        if (/[^A-Za-z0-9]/.test(pw)) {
            score++;
        }

        return Math.min(score, 4);
    });

    return { strength, strengthColors, strengthLabels };
}
