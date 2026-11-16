<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a phase stands, as stored. Whether an open phase is already over its limit depends on today's date
 * and is worked out when it is shown (App\Models\CaseDeadline::isOverdue()).
 */
enum DeadlineState: string implements HasLabel
{
    use HasTranslatedLabel;

    /** Started and not finished: the clock is running. */
    case Open = 'open';

    /** Finished within the limit. */
    case Met = 'met';

    /** Finished after the limit. */
    case Late = 'late';

    /** A later stage is recorded, so the phase is over, but its own end date is missing. */
    case Closed = 'closed';

    /** The investigation is suspended: the clock does not run. */
    case Paused = 'paused';
}
