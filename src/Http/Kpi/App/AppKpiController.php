<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Http\Kpi\App;

use App\Domain\Kpi\KpiResultRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AppKpiController extends AbstractController
{
    private KpiResultRepository $kpiResultRepository;

    public function __construct(KpiResultRepository $kpiResultRepository)
    {
        $this->kpiResultRepository = $kpiResultRepository;
    }

    #[Route('/kpi/results', name: 'app_kpi_results_index', methods: ['GET'])]
    public function listResults(#[CurrentUser] User $user) : Response
    {
        $this->denyAccessUnlessGranted('KPI_VIEW');

        $userId = $user->getId();
        $results = $this->kpiResultRepository->findResultsForUser($userId);

        return $this->render('kpi/results/index.html.twig', ['results' => $results]);
    }
}
