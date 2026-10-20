import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { Ref } from 'vue';

const FOCUSABLE =
    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Keyboard/accessibility helpers for custom (non-reka) modals:
 * Escape closes, Tab/Shift+Tab stay inside the dialog, focus starts inside
 * and returns to the trigger when the dialog closes.
 *
 * Usage: const { container } = useModalA11y(isOpen, close);
 * Bind `ref="container"` and `tabindex="-1"` on the dialog panel.
 */
export function useModalA11y(
    isOpen: Readonly<Ref<boolean>>,
    close: () => void,
) {
    const container = ref<HTMLElement | null>(null);
    let previouslyFocused: HTMLElement | null = null;

    function focusables(): HTMLElement[] {
        if (!container.value) {
            return [];
        }

        return Array.from(
            container.value.querySelectorAll<HTMLElement>(FOCUSABLE),
        ).filter(
            (el) => el.offsetParent !== null || el === document.activeElement,
        );
    }

    function onKeydown(event: KeyboardEvent): void {
        if (!isOpen.value) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            close();

            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const items = focusables();

        if (items.length === 0) {
            event.preventDefault();
            container.value?.focus();

            return;
        }

        const first = items[0];
        const last = items[items.length - 1];
        const active = document.activeElement as HTMLElement | null;

        if (
            event.shiftKey &&
            (active === first || !container.value?.contains(active))
        ) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        }
    }

    watch(isOpen, async (open) => {
        if (open) {
            previouslyFocused = document.activeElement as HTMLElement | null;
            await nextTick();
            const items = focusables();
            (items[0] ?? container.value)?.focus();
        } else if (previouslyFocused) {
            previouslyFocused.focus();
            previouslyFocused = null;
        }
    });

    onMounted(() => document.addEventListener('keydown', onKeydown));
    onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));

    return { container };
}
