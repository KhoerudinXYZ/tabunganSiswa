<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;

class SiswaPolicy
{
    /**
     * Determine whether the user can view the list of students.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Siswa $siswa): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($siswa->kelas === null) {
            return false; // Guru cannot manage students not assigned to any class
        }

        return $user->isGuru() && $user->isWaliKelasOf($siswa->kelas);
    }

    /**
     * Determine whether the user can create a student in the given class.
     * $kelas is null when only checking access to the "create" form in general.
     */
    public function create(User $user, ?Kelas $kelas = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isGuru()) {
            return false;
        }

        return $kelas === null || $user->isWaliKelasOf($kelas);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Siswa $siswa): bool
    {
        return $this->view($user, $siswa);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Siswa $siswa): bool
    {
        return $this->view($user, $siswa);
    }
}
