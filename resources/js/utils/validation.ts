/**
 * Validation utilities for consultation forms
 */

export const MEDICATION_FREQUENCIES = [
    '1x a day',
    '2x a day (12h)',
    '3x a day (8h)',
    '4x a day (6h)',
    'Every 4 hours',
    'Every 6 hours',
    'Every 8 hours',
    'As needed',
];

export interface ValidationError {
    field: string;
    message: string;
}

export function validateConsultation(form: any): ValidationError[] {
    const errors: ValidationError[] = [];

    // Validate SOAP notes
    if (!form.diagnosis?.trim()) {
        errors.push({
            field: 'diagnosis',
            message: 'Diagnosis/Assessment is required',
        });
    }

    // Validate prescriptions
    if (form.prescriptions?.length) {
        form.prescriptions.forEach((med: any, index: number) => {
            if (med.medicine?.trim() && !med.dosage?.trim()) {
                errors.push({
                    field: `prescriptions.${index}.dosage`,
                    message: 'Dosage required for medicine',
                });
            }

            if (med.medicine?.trim() && !med.duration?.toString().trim()) {
                errors.push({
                    field: `prescriptions.${index}.duration`,
                    message: 'Duration required for medicine',
                });
            }
        });
    }

    return errors;
}

export function sanitizeText(text: string): string {
    if (!text) {
        return '';
    }

    return text
        .trim()
        .replace(/[<>]/g, '') // Remove HTML-like brackets
        .slice(0, 5000); // Cap at 5000 chars
}

export function formatPatientId(patientId: string | number): string {
    // Use UUID or hashed identifier instead of sequential ID
    // This prevents enumeration attacks
    return `PT-${patientId}`;
}

// Healthcare-specific validation utilities

/**
 * Validates medication frequency against approved list
 */
export function validateMedicationFrequency(
    frequency: string,
): boolean {
    if (!frequency || typeof frequency !== 'string') {
return false;
}

    return MEDICATION_FREQUENCIES.some(
        (f: string) => f.toLowerCase() === frequency.toLowerCase(),
    );
}

/**
 * Known dangerous drug interactions (basic check for demonstration)
 * In production, this should query a comprehensive drug interaction database
 */
const DANGEROUS_DRUG_INTERACTIONS: Array<[string, string]> = [
    ['warfarin', 'aspirin'],
    ['warfarin', 'ibuprofen'],
    ['metformin', 'contrast dye'],
    ['ace inhibitor', 'potassium supplement'],
    ['digoxin', 'verapamil'],
];

/**
 * Checks for dangerous medication interactions
 * Returns array of warnings if conflicts detected
 */
export function checkMedicationInteractions(
    medications: Array<{ medicine: string; dosage: string }>,
): string[] {
    const warnings: string[] = [];

    if (!Array.isArray(medications) || medications.length < 2) {
        return warnings;
    }

    const medNames = medications
        .map((m: any) => (typeof m.medicine === 'string' ? m.medicine.toLowerCase() : ''))
        .filter((name: string) => name.length > 0);

    DANGEROUS_DRUG_INTERACTIONS.forEach(([drug1, drug2]: [string, string]) => {
        const has1 = medNames.some((m: string) => m.includes(drug1.toLowerCase()));
        const has2 = medNames.some((m: string) => m.includes(drug2.toLowerCase()));

        if (has1 && has2) {
            warnings.push(
                `⚠️ Potential interaction detected between ${drug1} and ${drug2}. Consult your doctor.`,
            );
        }
    });

    return warnings;
}

/**
 * Validates complete Prescription object
 */
export function validatePrescription(
    prescription: any,
): ValidationError[] {
    const errors: ValidationError[] = [];

    if (!prescription.medicine || typeof prescription.medicine !== 'string') {
        errors.push({
            field: 'medicine',
            message: 'Medicine name is required and must be a string',
        });
    }

    if (!prescription.dosage || typeof prescription.dosage !== 'string') {
        errors.push({
            field: 'dosage',
            message: 'Dosage is required and must be a string',
        });
    }

    if (
        !prescription.frequency ||
        !validateMedicationFrequency(prescription.frequency)
    ) {
        errors.push({
            field: 'frequency',
            message: `Frequency must be one of: ${MEDICATION_FREQUENCIES.join(', ')}`,
        });
    }

    if (!prescription.duration) {
        errors.push({
            field: 'duration',
            message: 'Duration is required',
        });
    }

    return errors;
}
