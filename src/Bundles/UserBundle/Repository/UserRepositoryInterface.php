<?php

namespace App\Bundles\UserBundle\Repository;

interface UserRepositoryInterface
{
    public function findByEmail(string $email) : ?array;
    public function findById(int $id) : ?array;
    public function findByEmailExcludingId(string $email, int $id) : ?array;
    public function findAllDoctors() : array;
    public function findAllActive() : array;
    public function save(array $data) : int|false;
    public function update(int $id, array $data) : bool;
    public function delete(int $id) : bool;
    public function countUsers() : int;
    public function updateProfilePhotoPath(int $userId, ?string $path) : bool;
}
