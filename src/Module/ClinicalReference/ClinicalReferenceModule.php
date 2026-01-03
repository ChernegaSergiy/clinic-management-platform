<?php

namespace App\Module\ClinicalReference;

use App\Core\BaseModule;
use App\Core\Router;
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
}