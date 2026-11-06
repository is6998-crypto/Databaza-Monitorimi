<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Maps legacy keys to new records so the importer can be re-run safely (idempotent).
 *
 * @property string $legacy_table
 * @property string $legacy_key
 * @property string $target_type
 * @property int $target_id
 */
class LegacyIdMap extends Model
{
    protected $table = 'legacy_id_map';

    protected $guarded = ['id'];
}
