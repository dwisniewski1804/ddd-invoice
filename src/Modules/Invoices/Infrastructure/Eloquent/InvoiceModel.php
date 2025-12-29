<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent model for invoices table.
 *
 * This is an infrastructure concern - it represents the database structure.
 * Mapping to/from domain entities is handled by InvoiceMapper.
 */
final class InvoiceModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'invoices';

    protected $fillable = [
        'id',
        'customer_name',
        'customer_email',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function productLines(): HasMany
    {
        return $this->hasMany(InvoiceProductLineModel::class, 'invoice_id');
    }
}
