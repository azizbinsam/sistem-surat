<?php

namespace App\Livewire\Concerns;

/**
 * Livewire's WithPagination punya default view sendiri (livewire::tailwind) yang
 * ke-override DI ATAS setting global Paginator::defaultView() di AppServiceProvider
 * (lihat Livewire\Features\SupportPagination::paginationView()). Jadi override-nya
 * harus di level komponen, bukan cukup dari service provider (Fase 17).
 */
trait HasCustomPagination
{
    public function paginationView(): string
    {
        return 'pagination.custom';
    }
}
