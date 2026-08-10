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

namespace App\Bundles\AppointmentBundle\Repository;

interface AppointmentRepositoryInterface
{
    public function findAll() : array;

    public function save(array $data) : int|false;

    public function findById(int $id) : ?array;

    public function findByPatientId(int $patientId) : array;

    public function update(int $id, array $data) : bool;

    public function updateStatus(int $id, string $status) : bool;

    // public function delete(int $id) : bool;

    public function findWaitlistById(int $id) : ?array;

    public function updateWaitlistStatus(int $id, string $status) : bool;

    public function generateWaitlistTicket() : string;

    public function isPatientAssignedToDoctor(int $patientId, int $doctorId) : bool;

    public function isAppointmentOwnedByDoctor(int $appointmentId, int $doctorId) : bool;

    public function findPatientIdsByDoctor(int $doctorId) : array;

    public function findByDoctorId(int $doctorId) : array;

    public function countScheduledByDate(string $date) : int;

    public function countScheduledByRange(string $from, string $to) : int;

    public function sumBookedHoursByRange(string $from, string $to) : float;

    public function countDistinctDoctorsByRange(string $from, string $to) : int;

    public function countReadmittedPatients(string $from, string $to) : int;

    public function countDistinctPatientsByRange(string $from, string $to) : int;

    public function findByDateRange(string $start, string $end) : array;

    public function findByDoctorIdAndDateRange(int $doctorId, string $start, string $end) : array;

    public function addToWaitlist(array $data) : bool;

    public function getWaitlistEntries(?string $status = 'pending') : array;

    public function getDoctorDailyLoad(string $date) : array;
}
