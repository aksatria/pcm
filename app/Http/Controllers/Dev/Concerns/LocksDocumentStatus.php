<?php

namespace App\Http\Controllers\Dev\Concerns;

trait LocksDocumentStatus
{
    protected function ensureEditableStatus($model, string $label = 'Dokumen'): void
    {
        $user = auth()->user();
        $isHO = $user && method_exists($user, 'isHO') && $user->isHO();
        if ($isHO) {
            return;
        }

        $status = strtolower((string) ($model->status ?? ''));
        $locked = ['approved', 'submitted', 'closed', 'final', 'cancelled'];

        if (in_array($status, $locked, true)) {
            abort(403, "{$label} sudah disubmit/approved dan tidak bisa diubah.");
        }
    }
}
