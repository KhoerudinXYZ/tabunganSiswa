<?php

namespace App\Policies;

use App\Models\Siswa;
use App\Models\Transaksi;
use App\Models\User;

class TransaksiPolicy
{
    /**
     * Determine whether the user can view the list of transactions.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isGuru();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaksi $transaksi): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isGuru() && $user->isWaliKelasOf($transaksi->siswa->kelas);
    }

    /**
     * Determine whether the user can create a transaction for the given student.
     * $siswa is null when only checking access to the "create" form in general.
     */
    public function create(User $user, ?Siswa $siswa = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isGuru()) {
            return false;
        }

        return $siswa === null || $user->isWaliKelasOf($siswa->kelas);
    }

    /**
     * Determine whether the user can void/correct the given transaction.
     */
    public function void(User $user, Transaksi $transaksi): bool
    {
        if ($transaksi->is_reversal || $transaksi->isVoided()) {
            return false;
        }

        return $this->view($user, $transaksi);
    }
}
