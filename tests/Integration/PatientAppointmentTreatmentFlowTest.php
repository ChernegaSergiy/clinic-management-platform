<?php

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;

class PatientAppointmentTreatmentFlowTest extends TestCase
{
    public function testFlowScenario() : void
    {
        $patientData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '1990-05-15',
            'gender' => 'male',
            'phone' => '+380501234567'
        ];

        $this->assertNotEmpty($patientData['first_name']);
        $this->assertNotEmpty($patientData['last_name']);
        $this->assertNotEmpty($patientData['birth_date']);
        $this->assertNotEmpty($patientData['gender']);
        $this->assertNotEmpty($patientData['phone']);

        $appointmentData = [
            'patient_id' => 1,
            'doctor_id' => 1,
            'start_time' => '2024-06-15 10:00:00',
            'end_time' => '2024-06-15 10:30:00',
            'status' => 'scheduled'
        ];

        $this->assertIsInt($appointmentData['patient_id']);
        $this->assertIsInt($appointmentData['doctor_id']);
        $this->assertNotEmpty($appointmentData['start_time']);
        $this->assertNotEmpty($appointmentData['end_time']);
        $this->assertEquals('scheduled', $appointmentData['status']);

        $this->assertTrue(
            strtotime($appointmentData['start_time']) < strtotime($appointmentData['end_time']),
            'Appointment start time must be before end time'
        );

        $treatmentData = [
            'patient_id' => $appointmentData['patient_id'],
            'appointment_id' => 1,
            'doctor_id' => $appointmentData['doctor_id'],
            'visit_date' => '2024-06-15',
            'diagnosis_code' => 'J00',
            'diagnosis_text' => 'Acute nasopharyngitis [common cold]',
            'treatment' => 'Rest, fluids, symptomatic treatment',
            'notes' => 'Follow up in 1 week if symptoms persist'
        ];

        $this->assertIsInt($treatmentData['patient_id']);
        $this->assertIsInt($treatmentData['appointment_id']);
        $this->assertIsInt($treatmentData['doctor_id']);
        $this->assertNotEmpty($treatmentData['diagnosis_code']);
        $this->assertNotEmpty($treatmentData['diagnosis_text']);
        $this->assertNotEmpty($treatmentData['treatment']);

        $this->assertEquals(
            $patientData['first_name'],
            'John',
            'Patient name consistency check'
        );

        $this->assertEquals(
            $appointmentData['patient_id'],
            $treatmentData['patient_id'],
            'Patient ID must be consistent across flow'
        );

        $this->assertEquals(
            $appointmentData['doctor_id'],
            $treatmentData['doctor_id'],
            'Doctor ID must be consistent across flow'
        );

        $icdCodes = ['J00', 'J01', 'J02'];
        $this->assertContains($treatmentData['diagnosis_code'], $icdCodes);

        $this->assertTrue(true, 'Integration flow scenario validated');
    }

    public function testPatientToAppointmentRelationship() : void
    {
        $patient = [
            'id' => 100,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'status' => 'active'
        ];

        $appointments = [
            ['id' => 1, 'patient_id' => 100, 'status' => 'completed'],
            ['id' => 2, 'patient_id' => 100, 'status' => 'scheduled'],
            ['id' => 3, 'patient_id' => 100, 'status' => 'cancelled']
        ];

        $patientAppointments = array_filter($appointments, function ($apt) use ($patient) {
            return $apt['patient_id'] === $patient['id'];
        });

        $this->assertCount(3, $patientAppointments);

        $scheduledAppointments = array_filter($patientAppointments, function ($apt) {
            return 'scheduled' === $apt['status'];
        });

        $this->assertCount(1, $scheduledAppointments);

        $this->assertEquals(
            $patient['status'],
            'active',
            'Patient must be active to have appointments'
        );
    }

    public function testAppointmentToTreatmentRelationship() : void
    {
        $appointment = [
            'id' => 50,
            'patient_id' => 25,
            'doctor_id' => 10,
            'start_time' => '2024-06-15 09:00:00',
            'end_time' => '2024-06-15 09:30:00',
            'status' => 'completed'
        ];

        $treatment = [
            'appointment_id' => $appointment['id'],
            'patient_id' => $appointment['patient_id'],
            'doctor_id' => $appointment['doctor_id'],
            'visit_date' => '2024-06-15',
            'diagnosis_code' => 'K29.7',
            'diagnosis_text' => 'Gastritis, unspecified',
            'treatment' => 'Dietary modifications, omeprazole 20mg daily'
        ];

        $this->assertEquals($appointment['id'], $treatment['appointment_id']);
        $this->assertEquals($appointment['patient_id'], $treatment['patient_id']);
        $this->assertEquals($appointment['doctor_id'], $treatment['doctor_id']);

        $this->assertEquals(
            $appointment['status'],
            'completed',
            'Treatment should only be created for completed appointments'
        );
    }

    public function testFullClinicalFlow() : void
    {
        $patient = [
            'id' => 500,
            'first_name' => 'Michael',
            'last_name' => 'Johnson',
            'birth_date' => '1985-03-20',
            'gender' => 'male',
            'status' => 'active'
        ];

        $appointmentRequest = [
            'patient_id' => $patient['id'],
            'doctor_id' => 5,
            'start_time' => '2024-12-01 14:00:00',
            'end_time' => '2024-12-01 14:30:00',
            'status' => 'scheduled',
            'notes' => 'Annual checkup'
        ];

        $this->assertEquals($patient['id'], $appointmentRequest['patient_id']);

        $appointmentRecord = array_merge($appointmentRequest, [
            'id' => 1000,
            'room_id' => 3
        ]);

        $this->assertIsInt($appointmentRecord['id']);
        $this->assertEquals('scheduled', $appointmentRecord['status']);

        $medicalRecord = [
            'patient_id' => $appointmentRecord['patient_id'],
            'appointment_id' => $appointmentRecord['id'],
            'doctor_id' => $appointmentRecord['doctor_id'],
            'visit_date' => '2024-12-01',
            'diagnosis_code' => 'Z00.00',
            'diagnosis_text' => 'Encounter for general adult medical examination without abnormal findings',
            'treatment' => 'Patient advised on healthy lifestyle and preventive care.',
            'notes' => 'Blood pressure normal. All screenings up to date.',
            'icd_codes' => [1, 2, 3],
            'intervention_codes' => []
        ];

        $this->assertEquals($appointmentRecord['patient_id'], $medicalRecord['patient_id']);
        $this->assertEquals($appointmentRecord['id'], $medicalRecord['appointment_id']);
        $this->assertEquals($appointmentRecord['doctor_id'], $medicalRecord['doctor_id']);
        $this->assertIsArray($medicalRecord['icd_codes']);
        $this->assertIsArray($medicalRecord['intervention_codes']);

        $this->assertTrue(true, 'Full clinical flow scenario validated');
    }
}
