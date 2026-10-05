# 🔧 QUICK REFERENCE: IMPLEMENTATION GUIDES

## 1️⃣ HOW TO CONVERT A COMPONENT TO TYPESCRIPT

### **Step 1: Update Script Tag**
```diff
- <script setup>
+ <script setup lang="ts">
+ import type { Ref } from 'vue';
```

### **Step 2: Define Props with Types**
```typescript
// ❌ OLD (No type checking)
const props = defineProps({
    user: { type: Object, required: true },
    items: { type: Array, required: true },
});

// ✅ NEW (Full type safety)
interface User {
    id: number;
    name: string;
    email: string;
}

interface Item {
    id: string;
    title: string;
}

const props = defineProps<{
    user: User;
    items: Item[];
}>();
```

### **Step 3: Type Your Methods**
```typescript
// ❌ OLD (Ambiguous)
const handleClick = (data) => {
    console.log(data.name); // TS doesn't know what data is
};

// ✅ NEW (Type-safe)
const handleClick = (item: Item): void => {
    console.log(item.title); // TS knows item has 'title'
};
```

### **Step 4: Type Reactive State**
```typescript
import { ref, computed } from 'vue';

// ✅ With types
const isLoading = ref<boolean>(false);
const count = ref<number>(0);
const items = ref<Item[]>([]);

// ✅ Computed
const filteredItems = computed((): Item[] => {
    return items.value.filter(item => item.title.includes(search.value));
});
```

---

## 2️⃣ HOW TO REPLACE ALERT() WITH TOAST

### **Step 1: Import Toast**
```typescript
import { useToast } from '@/composables/useToast';
import Toast from '@/Components/Toast.vue';

const { success, error, info } = useToast();
```

### **Step 2: Add Toast Component to Template**
```vue
<template>
    <div>
        <Toast />  <!-- Add this at top level -->
        <!-- Rest of your template -->
    </div>
</template>
```

### **Step 3: Replace alert() with Toast Methods**
```typescript
// ❌ OLD
alert('Success!');
alert('Error: ' + error.message);

// ✅ NEW
showSuccess('Appointment booked successfully!');
showError(`Failed: ${error.message}`);
showInfo('Processing your request...');
```

### **Step 4: Usage in Event Handlers**
```typescript
const handleSubmit = async () => {
    try {
        const response = await fetch('/api/submit', { method: 'POST' });
        if (response.ok) {
            showSuccess('Form submitted!');
            // Navigate or refresh
        } else {
            showError('Submission failed');
        }
    } catch (err) {
        showError(`Error: ${err.message}`);
    }
};
```

---

## 3️⃣ HOW TO ADD ERROR HANDLERS TO FORMS

### **Pattern for Inertia Forms**
```typescript
// ❌ OLD (No error handling)
form.post('/submit', {
    preserveScroll: true,
    onSuccess: () => alert('Success!')
});

// ✅ NEW (Full error handling)
form.post('/submit', {
    preserveScroll: true,
    onSuccess: () => {
        showSuccess('Operation completed!');
        // Navigate or refresh
    },
    onError: (errors: Record<string, string>) => {
        const errorMsg = errors.general || 'Operation failed';
        showError(errorMsg);
        console.error('Form errors:', errors);
    },
});
```

---

## 4️⃣ HOW TO ADD LOADING STATES

### **Prevent Duplicate Submissions**
```typescript
import { ref } from 'vue';

const isSubmitting = ref<boolean>(false);

const handleSubmit = async (): Promise<void> => {
    if (isSubmitting.value) return; // Prevent double-click
    
    isSubmitting.value = true;
    try {
        await form.post('/submit');
        showSuccess('Saved!');
    } finally {
        isSubmitting.value = false;
    }
};
```

### **In Template**
```vue
<button 
    @click="handleSubmit" 
    :disabled="isSubmitting"
    class="disabled:opacity-50 disabled:cursor-wait"
>
    <span v-if="isSubmitting">Loading...</span>
    <span v-else>Submit</span>
</button>
```

---

## 5️⃣ HOW TO USE PATIENT ID SAFELY

### **Helper Function**
```typescript
// Place in a utils file or component
function formatPatientId(patientId: string | number): string {
    // This prevents enumeration attacks by not exposing sequential IDs
    return `PT-${patientId}`;
}

// Usage in template:
<p>Patient ID: {{ formatPatientId(patient.id) }}</p>
```

### **Backend (PHP)**
```php
// Already implemented in AppointmentController
private function sanitizeText(string $text, int $maxLength = 5000): string
{
    return trim(
        htmlspecialchars(
            substr($text, 0, $maxLength),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}
```

---

## 6️⃣ HOW TO VALIDATE FORMS

### **Client-Side Validation**
```typescript
import { validateConsultation, sanitizeText } from '@/utils/validation';

const handleSave = (): void => {
    // Sanitize inputs first
    const sanitized = {
        symptoms: sanitizeText(form.symptoms),
        vitals: sanitizeText(form.vitals),
        diagnosis: sanitizeText(form.diagnosis),
    };

    // Validate
    const errors = validateConsultation(sanitized);
    
    if (errors.length > 0) {
        showError(`${errors.length} validation errors found`);
        errors.forEach(err => console.error(`${err.field}: ${err.message}`));
        return;
    }

    // If valid, proceed with save
    saveData();
};
```

---

## 7️⃣ HOW TO HANDLE JSON PARSING SAFELY

### **Safe JSON Parsing Pattern**
```typescript
// ❌ Unsafe (can crash)
const data = JSON.parse(jsonString);

// ✅ Safe (with error handling)
function parseJSON<T>(jsonString: string, defaultValue: T): T {
    try {
        return JSON.parse(jsonString) as T;
    } catch (e) {
        console.error('JSON parse error:', e);
        showError('Failed to parse data');
        return defaultValue;
    }
}

// Usage
const prescriptions = parseJSON<Prescription[]>(
    prescriptionString,
    []  // default empty array
);
```

---

## 8️⃣ TYPE DEFINITIONS TO COPY-PASTE

### **Common Patient Types**
```typescript
// resources/js/types/patient.ts
export interface Patient {
    id: string | number;
    name: string;
    email: string;
    phone?: string;
    patient_id: string | number;
    age?: number;
    sex?: 'Male' | 'Female' | 'Other';
}

export interface ConsultationRecord {
    id: string | number;
    patient_id: string | number;
    date: string;
    time: string;
    doctor?: string;
    diagnosis?: string;
    symptoms?: string;
    vitals?: string;
    prescriptions?: Prescription[];
    ai_summary?: string;
}

export interface Prescription {
    medicine: string;
    dosage: string;
    frequency: string;
    duration: string | number;
}

export interface User {
    id: string | number;
    name: string;
    email: string;
    role: 'doctor' | 'patient' | 'receptionist' | 'admin';
}
```

---

## 9️⃣ COMMON TYPESCRIPT PATTERNS

### **Optional Props**
```typescript
interface Props {
    required_field: string;
    optional_field?: string;  // Optional (can be undefined)
    field_with_default?: string;
}

const props = withDefaults(defineProps<Props>(), {
    field_with_default: 'default value'
});
```

### **Union Types**
```typescript
type Status = 'pending' | 'in_progress' | 'completed' | 'failed';

const checkStatus = (status: Status): string => {
    switch(status) {
        case 'pending': return 'Waiting...';
        case 'in_progress': return 'Processing...';
        case 'completed': return 'Done!';
        case 'failed': return 'Error!';
    }
};
```

### **Async Functions**
```typescript
// Explicitly typed async function
const fetchPatient = async (id: string): Promise<Patient> => {
    const response = await fetch(`/api/patients/${id}`);
    return response.json() as Promise<Patient>;
};
```

---

## 🔟 CHECKLIST FOR EACH COMPONENT CONVERSION

- [ ] Change `<script setup>` to `<script setup lang="ts">`
- [ ] Import types at top: `import type { ComponentName } from '@/types'`
- [ ] Add `defineProps<{ prop1: Type; prop2: Type }>()`
- [ ] Type all methods: `const method = (param: Type): ReturnType => {}`
- [ ] Replace `alert()` with `useToast()`
- [ ] Add `Toast` component to template
- [ ] Add error handlers to async operations
- [ ] Add loading states to buttons
- [ ] Type ref() and reactive() values
- [ ] Run `npm run build` to verify no errors
- [ ] Test in browser

---

## 📚 ADDITIONAL RESOURCES

### TypeScript Docs
- Vue TypeScript: https://vuejs.org/guide/typescript/
- TypeScript Handbook: https://www.typescriptlang.org/docs/

### Your Project's Examples
- `Doctor.vue` - Full TypeScript example with validation
- `PatientHistory.vue` - Full TypeScript example with async handling
- `validation.ts` - Reusable validation utilities
- `useToast.ts` - Toast notification pattern

---

**Questions?** Refer to Doctor.vue or PatientHistory.vue for working examples of all patterns shown above.
