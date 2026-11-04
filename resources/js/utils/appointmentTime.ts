export const APPOINTMENT_SLOTS = [
    { value: 'morning', label: 'Morning (6:00 AM - 12:00 PM)' },
    { value: 'afternoon', label: 'Afternoon (1:00 PM - 6:00 PM)' },
] as const;

export type AppointmentSlotValue = (typeof APPOINTMENT_SLOTS)[number]['value'];

/**
 * Map a stored exact time (or a legacy formatted time) to the half-day window
 * it belongs to. Returns an empty string when the time is outside both
 * windows so callers can fall back to the raw value.
 */
export function timeSlotValue(time?: string | null): AppointmentSlotValue | '' {
    if (!time) {
        return '';
    }

    const value = String(time);

    if (value === 'morning' || value === 'afternoon') {
        return value;
    }

    const exact = value.slice(0, 5);

    if (exact >= '06:00' && exact < '13:00') {
        return 'morning';
    }

    if (exact >= '13:00' && exact <= '18:00') {
        return 'afternoon';
    }

    return '';
}

export function appointmentSlotLabel(time?: string | null): string {
    if (!time) {
        return 'N/A';
    }

    const value = String(time);

    if (value.startsWith('Morning') || value.startsWith('Afternoon')) {
        return value;
    }

    const slot = timeSlotValue(value);

    if (slot) {
        return APPOINTMENT_SLOTS.find((option) => option.value === slot)!.label;
    }

    return value;
}
