# Access Rights and Policies System

## Overview

Symfony Voters + `role_hierarchy` in `security.yaml`. The hierarchy is the **single source of truth** for all permission grants.

## Naming Convention

```
ROLE_{DOMAIN}_{ACTION}_{SCOPE}
```

| Component | Values | Notes |
|-----------|--------|-------|
| `DOMAIN` | `APPOINTMENT`, `PATIENT`, `MEDICAL_RECORD`, etc. | Singular noun |
| `ACTION` | `VIEW`, `CREATE`, `EDIT`, `DELETE`, `CANCEL`, `MANAGE`, `EXPORT` | `MANAGE` = full CRUD |
| `SCOPE` | `ANY`, `OWN` | Optional; omitted for admin-only permissions |

Examples:
- `ROLE_APPOINTMENT_VIEW_ANY` — view all appointments
- `ROLE_APPOINTMENT_VIEW_OWN` — view only own appointments
- `ROLE_DEPARTMENT_MANAGE` — full department management (admin-only, no scope)

## Voter Attribute Convention

Voter attributes are the **same strings without `ROLE_` prefix**:

```php
public const VIEW_ANY = 'APPOINTMENT_VIEW_ANY';
public const VIEW_OWN = 'APPOINTMENT_VIEW_OWN';
```

## Role Hierarchy (`security.yaml`)

The hierarchy defines which roles inherit which permissions:

```yaml
security:
    role_hierarchy:
        ROLE_ADMIN:
            - ROLE_APPOINTMENT_VIEW_ANY
            - ROLE_APPOINTMENT_CREATE
            - ROLE_APPOINTMENT_EDIT_ANY
            - ROLE_APPOINTMENT_DELETE
            # ... other admin permissions

        ROLE_DOCTOR:
            - ROLE_APPOINTMENT_VIEW_OWN
            - ROLE_APPOINTMENT_CREATE
            - ROLE_APPOINTMENT_EDIT_OWN
            - ROLE_APPOINTMENT_CANCEL_OWN
            # ... other doctor permissions

        ROLE_NURSE:
            - ROLE_APPOINTMENT_VIEW_OWN
            - ROLE_PATIENT_VIEW_OWN
            - ROLE_MEDICAL_RECORD_VIEW_OWN
            # ... other nurse permissions
```

**Key rules:**
- `_ANY` = access to all records
- `_OWN` = access to own records only
- `MANAGE` = full CRUD (used for admin-only domains like `DEPARTMENT_MANAGE`)
- `VIEW` = read-only access
- `CREATE` = can create new records
- `EDIT` = can modify existing records
- `DELETE` = can remove records
- `CANCEL` = can cancel appointments/prescriptions
- `EXPORT` = can export data

## Voter Implementation

### Ownership-free voters (admin-only domains)

```php
class DepartmentVoter extends Voter
{
    public const VIEW = 'DEPARTMENT_VIEW';
    public const CREATE = 'DEPARTMENT_CREATE';
    public const EDIT = 'DEPARTMENT_EDIT';
    public const DELETE = 'DEPARTMENT_DELETE';
    public const MANAGE = 'DEPARTMENT_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE, self::MANAGE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->security->isGranted('ROLE_DEPARTMENT_VIEW'),
            self::CREATE => $this->security->isGranted('ROLE_DEPARTMENT_CREATE'),
            self::EDIT => $this->security->isGranted('ROLE_DEPARTMENT_EDIT'),
            self::DELETE => $this->security->isGranted('ROLE_DEPARTMENT_DELETE'),
            self::MANAGE => $this->security->isGranted('ROLE_DEPARTMENT_MANAGE'),
            default => false,
        };
    }
}
```

### Ownership voters (domain + entity ownership)

```php
class AppointmentVoter extends Voter
{
    public const VIEW_ANY = 'APPOINTMENT_VIEW_ANY';
    public const VIEW_OWN = 'APPOINTMENT_VIEW_OWN';
    public const CREATE = 'APPOINTMENT_CREATE';
    public const EDIT_ANY = 'APPOINTMENT_EDIT_ANY';
    public const EDIT_OWN = 'APPOINTMENT_EDIT_OWN';
    public const CANCEL_ANY = 'APPOINTMENT_CANCEL_ANY';
    public const CANCEL_OWN = 'APPOINTMENT_CANCEL_OWN';
    public const DELETE = 'APPOINTMENT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW_ANY, self::VIEW_OWN,
            self::CREATE,
            self::EDIT_ANY, self::EDIT_OWN,
            self::CANCEL_ANY, self::CANCEL_OWN,
            self::DELETE,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW_ANY => $this->security->isGranted('ROLE_APPOINTMENT_VIEW_ANY'),
            self::VIEW_OWN => $this->security->isGranted('ROLE_APPOINTMENT_VIEW_OWN') && $this->isOwner($subject, $user),
            self::CREATE => $this->security->isGranted('ROLE_APPOINTMENT_CREATE'),
            self::EDIT_ANY => $this->security->isGranted('ROLE_APPOINTMENT_EDIT_ANY'),
            self::EDIT_OWN => $this->security->isGranted('ROLE_APPOINTMENT_EDIT_OWN') && $this->isOwner($subject, $user),
            self::CANCEL_ANY => $this->security->isGranted('ROLE_APPOINTMENT_CANCEL_ANY'),
            self::CANCEL_OWN => $this->security->isGranted('ROLE_APPOINTMENT_CANCEL_OWN') && $this->isOwner($subject, $user),
            self::DELETE => $this->security->isGranted('ROLE_APPOINTMENT_DELETE'),
            default => false,
        };
    }
}
```

## Adding New Permissions

1. **Define roles** in `config/packages/security.yaml` under `role_hierarchy`
2. **Add constants** to the Voter class
3. **Implement `supports()`** and **`voteOnAttribute()`** using `ROLE_*` strings
4. **Update controllers** to use new attribute names

## Current Voter Inventory

| Voter | Attributes | Ownership |
|-------|-----------|-----------|
| `AppointmentVoter` | `VIEW_ANY/OWN`, `CREATE`, `EDIT_ANY/OWN`, `CANCEL_ANY/OWN`, `DELETE` | Yes |
| `PatientVoter` | `VIEW_ANY/OWN`, `CREATE`, `EDIT_ANY/OWN`, `DELETE` | Yes |
| `MedicalRecordVoter` | `VIEW_ANY/OWN`, `CREATE`, `EDIT_OWN`, `DELETE` | Yes |
| `PrescriptionVoter` | `VIEW_ANY/OWN`, `CREATE`, `EDIT_OWN`, `CANCEL_OWN`, `DELETE` | Yes |
| `LabOrderVoter` | `VIEW_ANY/OWN`, `CREATE`, `EDIT_ANY/OWN`, `DELETE` | Yes |
| `ScheduleVoter` | `VIEW_ANY/OWN`, `MANAGE_ANY/OWN`, `DELETE` | Yes |
| `DepartmentVoter` | `VIEW`, `CREATE`, `EDIT`, `DELETE`, `MANAGE` | No |
| `HrmVoter` | `VIEW`, `EDIT`, `MANAGE` | No |
| `BillingVoter` | `VIEW`, `CREATE`, `EDIT`, `DELETE`, `MANAGE` | No |
| `InsuranceVoter` | `VIEW`, `CREATE`, `EDIT`, `DELETE` | No |
| `NewsVoter` | `VIEW`, `CREATE`, `EDIT`, `DELETE` | No |
| `RoomVoter` | `VIEW`, `CREATE`, `EDIT`, `DELETE`, `MANAGE` | No |
| `NotificationVoter` | `VIEW`, `CREATE`, `EDIT`, `DELETE` | No |
| `ClinicalReferenceVoter` | `VIEW`, `CREATE`, `EDIT`, `DELETE` | No |
| `DashboardVoter` | `VIEW` | No |
| `KpiVoter` | `VIEW` | No |
| `InventoryVoter` | `VIEW`, `CREATE`, `EDIT`, `DELETE`, `MANAGE` | No |
