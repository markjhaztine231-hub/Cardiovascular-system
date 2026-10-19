import { computed, ref, watch, type Ref } from 'vue';

interface SidebarState {
    isOpen: Ref<boolean>;
    isMobileOpen: Ref<boolean>;
}

const states = new Map<string, SidebarState>();

const isMobile = ref(false);

if (typeof window !== 'undefined') {
    const media = window.matchMedia('(max-width: 767px)');
    isMobile.value = media.matches;

    media.addEventListener('change', (event) => {
        isMobile.value = event.matches;
    });
}

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

    /** Whether the sidebar is currently on-screen for the active viewport. */
    const isSidebarOpen = computed<boolean>(() =>
        isMobile.value ? isMobileOpen.value : isOpen.value,
    );

    function toggleDesktop(): void {
        isOpen.value = !isOpen.value;
    }

    function toggleMobile(): void {
        isMobileOpen.value = !isMobileOpen.value;
    }

    function closeMobile(): void {
        isMobileOpen.value = false;
    }

    function toggle(): void {
        if (isMobile.value) {
            toggleMobile();
        } else {
            toggleDesktop();
        }
    }

    return {
        isOpen,
        isMobileOpen,
        isMobile,
        isSidebarOpen,
        toggleDesktop,
        toggleMobile,
        toggle,
        closeMobile,
    };
}
