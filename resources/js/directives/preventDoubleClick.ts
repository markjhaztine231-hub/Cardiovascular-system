import type { Directive, DirectiveBinding } from 'vue';

const CLICK_COOLDOWN = new WeakMap<HTMLElement, number>();

const vPreventDoubleClick: Directive<HTMLElement, number | undefined> = {
    mounted(el: HTMLElement, binding: DirectiveBinding<number | undefined>) {
        const cooldown = binding.value ?? 1500;

        const handler = (e: Event): void => {
            const lastClick = CLICK_COOLDOWN.get(el) ?? 0;
            const now = Date.now();

            if (now - lastClick < cooldown) {
                e.preventDefault();
                e.stopPropagation();

                return;
            }

            CLICK_COOLDOWN.set(el, now);

            if (el instanceof HTMLButtonElement || el instanceof HTMLInputElement) {
                el.disabled = true;
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        el.disabled = false;
                    }, cooldown);
                });
            }
        };

        el.addEventListener('click', handler, true);

        (el as any)._preventDoubleClickHandler = handler;
    },

    unmounted(el: HTMLElement) {
        const handler = (el as any)._preventDoubleClickHandler;

        if (handler) {
            el.removeEventListener('click', handler, true);
            delete (el as any)._preventDoubleClickHandler;
        }

        CLICK_COOLDOWN.delete(el);
    },
};

export default vPreventDoubleClick;
