<?php

namespace App\Module\ClinicalReference;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use App\Module\ClinicalReference\ClinicalReferenceController;

class ClinicalReferenceModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/admin/clinical', [ClinicalReferenceController::class, 'clinicalIndex']);
        $router->add('GET', '/admin/clinical/icd-import', [ClinicalReferenceController::class, 'icdImportForm']);
        $router->add('POST', '/admin/clinical/icd-import', [ClinicalReferenceController::class, 'icdImportRun']);
        $router->add('GET', '/admin/clinical/intervention-import', [ClinicalReferenceController::class, 'interventionImportForm']);
        $router->add('POST', '/admin/clinical/intervention-import', [ClinicalReferenceController::class, 'interventionImportRun']);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('clinical.manage', 'Керування клінічними довідниками');

        $registry->addRoleMapping('admin', ['clinical.manage']);
        $registry->addRoleMapping('medical_manager', ['clinical.manage']);
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
        $registry->register('clinical', ClinicalReferencePolicy::class);
    }
}
