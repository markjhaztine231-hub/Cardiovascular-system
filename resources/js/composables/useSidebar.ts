import { ref, watch, type Ref } from 'vue';

interface SidebarState {
    isOpen: Ref<boolean>;
    isMobileOpen: Ref<boolean>;
}

const states = new Map<string, SidebarState>();

function getState(storageKey: string): SidebarState {
    let state = states.get(storageKey);

    if (!state) {
        const isOpen = ref<boolean>(true);
        const isMobileOpen = ref<boolean>(false);

        if (typeof window !== 'undefined') {
            const saved = window.localStorage.getItem(storageKey);
            if (saved !== null) {
                isOpen.value = saved !== 'false';
            }
        }

        watch(isOpen, (value) => {
            if (typeof window !== 'undefined') {
                window.localStorage.setItem(
                    storageKey,
                    value ? 'true' : 'false',
                );
            }
        });

        state = { isOpen, isMobileOpen };
        states.set(storageKey, state);
    }

    return state;
}

export function useSidebar(storageKey: string = 'cardiocare-sidebar-open') {
    const { isOpen, isMobileOpen } = getState(storageKey);

    function toggleDesktop(): void {
        isOpen.value = !isOpen.value;
    }

    function toggleMobile(): void {
        isMobileOpen.value = !isMobileOpen.value;
    }

    function closeMobile(): void {
        isMobileOpen.value = false;
    }

    return {
        isOpen,
        isMobileOpen,
        toggleDesktop,
        toggleMobile,
        closeMobile,
    };
}
