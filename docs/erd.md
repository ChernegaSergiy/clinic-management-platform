# ER-діаграма основних сутностей

> **Примітка:** Ця діаграма є базовим проєктом. Вона буде розширена та деталізована відповідно до вимог Національної служби здоров'я України (НСЗУ) та стандартів електронної системи охорони здоров'я (eHealth), включаючи специфічні ідентифікатори, довідники та обов'язкові поля.

## 1. Сутності та їх атрибути

### User (Користувач)
-   `id` (PK, INT)
-   `username` (VARCHAR)
-   `password_hash` (VARCHAR)
-   `email` (VARCHAR, UNIQUE)
-   `first_name` (VARCHAR)
-   `last_name` (VARCHAR)
-   `role_id` (FK, INT)
-   `created_at` (DATETIME)
-   `updated_at` (DATETIME)

### Role (Роль)
-   `id` (PK, INT)
-   `name` (VARCHAR, UNIQUE) (e.g., 'admin', 'doctor', 'registrar')
-   `description` (TEXT)

### Patient (Пацієнт)
-   `id` (PK, INT)
-   `first_name` (VARCHAR)
-   `last_name` (VARCHAR)
-   `middle_name` (VARCHAR, NULLABLE)
-   `birth_date` (DATE)
-   `gender` (ENUM('male', 'female', 'other'))
-   `phone` (VARCHAR)
-   `email` (VARCHAR, UNIQUE, NULLABLE)
-   `address` (TEXT, NULLABLE)
-   `tax_id` (VARCHAR, UNIQUE, NULLABLE) - РНОКПП
-   `document_id` (VARCHAR, UNIQUE, NULLABLE) - ID-картка/паспорт
-   `ehealth_patient_id` (UUID, UNIQUE, NULLABLE) - Ідентифікатор пацієнта в eHealth
-   `created_at` (DATETIME)
-   `updated_at` (DATETIME)

### Appointment (Прийом/Епізод)
-   `id` (PK, INT)
-   `patient_id` (FK, INT)
-   `doctor_id` (FK, INT) (FK до User, де role_id = doctor)
-   `start_time` (DATETIME)
-   `end_time` (DATETIME)
-   `status` (ENUM('scheduled', 'completed', 'cancelled', 'no-show'))
-   `ehealth_episode_id` (UUID, UNIQUE, NULLABLE) - Ідентифікатор епізоду в eHealth
-   `notes` (TEXT, NULLABLE)
-   `created_at` (DATETIME)
-   `updated_at` (DATETIME)

### MedicalRecord (Медичний запис)
-   `id` (PK, INT)
-   `patient_id` (FK, INT)
-   `appointment_id` (FK, INT)
-   `doctor_id` (FK, INT) (FK до User, де role_id = doctor)
-   `visit_date` (DATETIME)
-   `diagnosis_code` (VARCHAR) - Код за ICD-10
-   `diagnosis_text` (TEXT)
-   `treatment` (TEXT)
-   `ehealth_record_id` (UUID, UNIQUE, NULLABLE) - Ідентифікатор запису в eHealth
-   `notes` (TEXT, NULLABLE)
-   `created_at` (DATETIME)
-   `updated_at` (DATETIME)

## 2. Взаємозв'язки (Relationships)

```mermaid
erDiagram
    ROLE {
        INT id PK
        VARCHAR name UK
        TEXT description
    }

    USER {
        INT id PK
        VARCHAR username UK
        VARCHAR password_hash
        VARCHAR email UK
        VARCHAR first_name
        VARCHAR last_name
        INT role_id FK
        DATETIME created_at
        DATETIME updated_at
    }

    PATIENT {
        INT id PK
        VARCHAR first_name
        VARCHAR last_name
        VARCHAR middle_name
        DATE birth_date
        ENUM gender
        VARCHAR phone
        VARCHAR email UK
        TEXT address
        VARCHAR tax_id UK "РНОКПП"
        VARCHAR document_id UK "ID-картка/паспорт"
        UUID ehealth_patient_id UK "ID пацієнта в eHealth"
        DATETIME created_at
        DATETIME updated_at
    }

    APPOINTMENT {
        INT id PK
        INT patient_id FK
        INT doctor_id FK "User (role=doctor)"
        DATETIME start_time
        DATETIME end_time
        ENUM status
        UUID ehealth_episode_id UK "ID епізоду в eHealth"
        TEXT notes
        DATETIME created_at
        DATETIME updated_at
    }

    MEDICAL_RECORD {
        INT id PK
        INT patient_id FK
        INT appointment_id FK
        INT doctor_id FK "User (role=doctor)"
        DATETIME visit_date
        VARCHAR diagnosis_code "ICD-10"
        TEXT diagnosis_text
        TEXT treatment
        UUID ehealth_record_id UK "ID запису в eHealth"
        TEXT notes
        DATETIME created_at
        DATETIME updated_at
    }

    ROLE ||--o{ USER : "has"
    USER ||--o{ APPOINTMENT : "schedules"
    USER ||--o{ MEDICAL_RECORD : "creates"
    PATIENT ||--o{ APPOINTMENT : "has"
    PATIENT ||--o{ MEDICAL_RECORD : "has"
    APPOINTMENT ||--o{ MEDICAL_RECORD : "contains"
```
