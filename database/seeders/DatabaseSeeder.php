<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. System Admin
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'Active',
        ]);

        // 2. Receptionist
        User::create([
            'name' => 'Jane Doe',
            'email' => 'reception@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'receptionist',
            'status' => 'Active',
        ]);

        // 3. Doctors with provider credentials
        $chen = User::create([
            'name' => 'Dr. Michael Chen',
            'email' => 'chen@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'status' => 'Active',
        ]);

        $doctor1 = Provider::create([
            'user_id' => $chen->id,
            'npi' => '1487023456',
            'license_number' => 'PRC-123456',
            'specialty' => 'Cardiology',
            'credentials' => 'MD',
        ]);

        $smith = User::create([
            'name' => 'Dr. Sarah Smith',
            'email' => 'smith@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'status' => 'Active',
        ]);

        $doctor2 = Provider::create([
            'user_id' => $smith->id,
            'npi' => '1689876123',
            'license_number' => 'PRC-654321',
            'specialty' => 'Cardiology / Interventional',
            'credentials' => 'MD',
        ]);

        // 4. Patient with clinical demographics
        $user = User::create([
            'name' => 'John Patient',
            'email' => 'patient@clinic.com',
            'password' => Hash::make('password'),
            'role' => 'patient',
            'status' => 'Active',
        ]);

        $patient = Patient::create([
            'user_id' => $user->id,
            'mrn' => 'MRN-00001',
            'date_of_birth' => '1980-05-12',
            'sex' => 'Male',
            'blood_type' => 'O+',
            'contact_phone' => '0917-123-4567',
            'address' => 'Manila, Philippines',
            'emergency_contact_name' => 'Maria Patient',
            'emergency_contact_phone' => '0918-111-2222',
            'allergies' => 'Penicillin, Peanuts',
            'insurance_provider' => 'PhilHealth',
            'insurance_id' => 'PH-0000-1111-2222',
        ]);

        // 5. Weekly availability (Mon-Fri, 8AM-5PM)
        DoctorSchedule::create([
            'provider_id' => $doctor1->id,
            'day_of_week' => [1, 2, 3, 4, 5],
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);

        DoctorSchedule::create([
            'provider_id' => $doctor2->id,
            'day_of_week' => [1, 3, 5],
            'start_time' => '09:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        // 6. Today's appointments so the dashboard has data
        Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $doctor1->id,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '10:00:00',
            'service_type' => 'Procedure',
            'status' => 'Scheduled',
            'reason_for_visit' => 'Routine cardiovascular checkup',
        ]);

        Appointment::create([
            'patient_id' => $patient->id,
            'provider_id' => $doctor2->id,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '14:30:00',
            'service_type' => 'Procedure',
            'status' => 'Scheduled',
            'reason_for_visit' => 'Follow-up echocardiogram review',
        ]);

        // 7. HIPAA-compliant audit trail
        AuditLog::create([
            'user_id' => $admin->id,
            'patient_id' => $patient->id,
            'action' => 'created',
            'resource_type' => 'patient',
            'resource_id' => $patient->id,
            'description' => 'Patient record initialized during seeding.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'seeder',
        ]);
    }
}
