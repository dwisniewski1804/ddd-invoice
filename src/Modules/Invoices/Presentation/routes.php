<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Presentation\Http\InvoiceController;
use Ramsey\Uuid\Validator\GenericValidator;

Route::pattern('id', (new GenericValidator)->getPattern());

Route::prefix('invoices')->group(function (): void {
    Route::post('/', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::get('/{id}', [InvoiceController::class, 'view'])->name('invoices.view');
    Route::post('/{id}/send', [InvoiceController::class, 'send'])->name('invoices.send');
});
