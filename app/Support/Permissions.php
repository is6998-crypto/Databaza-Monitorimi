<?php

namespace App\Support;

use App\Enums\Role;

/**
 * Single source of truth for permissions and the default role matrix (proposal B4).
 *
 * Names follow Filament Shield's convention ("Affix:Subject", PascalCase) so the
 * Shield role editor shows them under the matching resource. Policies add the
 * record-level rules (monitor court scope, workflow state) on top of these.
 */
final class Permissions
{
    public const CRUD = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete'];

    public const READ = ['ViewAny', 'View'];

    /** Reference-data models (proposal B5). */
    public const REFERENCE_SUBJECTS = [
        'Court', 'CourtBranch', 'Department', 'Division', 'ProsecutionOffice',
        'Judge', 'Prosecutor', 'Monitor', 'Applicant',
        'CriminalCode', 'CriminalOffence', 'Category', 'Nationality',
        'DefendantPosition', 'EconomicStatus', 'SecurityMeasureType',
    ];

    public const CORE_SUBJECTS = ['CourtCase', 'Hearing', 'Attachment'];

    public const ADMIN_SUBJECTS = ['User', 'Role', 'Activity', 'ExportLog', 'ResearcherProfile', 'LegacyReviewItem', 'Publication'];

    /** Custom (non-CRUD) permissions. */
    public const REVIEW = 'Review:Workflow';

    public const PUBLISH = 'Publish:Workflow';

    public const MERGE_REFERENCE = 'Merge:Reference';

    public const IMPORT_REFERENCE = 'Import:Reference';

    public const EXPORT_DATA = 'Export:Data';

    public const ASSIGN_MONITOR = 'Assign:Monitor';

    public const VIEW_ANALYTICS = 'View:Analytics';

    public const APPROVE_RESEARCHERS = 'Approve:Researcher';

    public const MANAGE_SETTINGS = 'Manage:Settings';

    public const CUSTOM = [
        self::REVIEW, self::PUBLISH, self::MERGE_REFERENCE, self::IMPORT_REFERENCE,
        self::EXPORT_DATA, self::ASSIGN_MONITOR, self::VIEW_ANALYTICS,
        self::APPROVE_RESEARCHERS, self::MANAGE_SETTINGS,
    ];

    public static function name(string $affix, string $subject): string
    {
        return $affix.':'.$subject;
    }

    /**
     * @param  list<string>  $affixes
     * @param  list<string>  $subjects
     * @return list<string>
     */
    public static function for(array $affixes, array $subjects): array
    {
        $names = [];

        foreach ($subjects as $subject) {
            foreach ($affixes as $affix) {
                $names[] = self::name($affix, $subject);
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique([
            ...self::for(self::CRUD, [...self::REFERENCE_SUBJECTS, ...self::CORE_SUBJECTS, ...self::ADMIN_SUBJECTS]),
            ...self::CUSTOM,
        ]));
    }

    /**
     * Default role → permissions matrix. Super Admin is granted everything through a Gate::before hook.
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        $readEverything = self::for(self::READ, [...self::REFERENCE_SUBJECTS, ...self::CORE_SUBJECTS, 'Publication']);

        return [
            Role::SuperAdmin->value => self::all(),

            // Program manager: review, approve/return, edit any case, manage reference data, publish reports.
            Role::Editor->value => array_values(array_unique([
                ...self::for(['ViewAny', 'View', 'Create', 'Update', 'Restore'], self::REFERENCE_SUBJECTS),
                ...self::for(['ViewAny', 'View', 'Create', 'Update'], self::CORE_SUBJECTS),
                ...self::for(['ViewAny', 'View', 'Create', 'Update', 'Delete'], ['Publication']),
                ...self::for(['ViewAny', 'View', 'Update'], ['LegacyReviewItem']),
                ...self::for(self::READ, ['ExportLog']),
                self::REVIEW, self::PUBLISH, self::MERGE_REFERENCE, self::IMPORT_REFERENCE,
                self::EXPORT_DATA, self::ASSIGN_MONITOR, self::VIEW_ANALYTICS,
            ])),

            // Monitor: create cases and hearings, edit own drafts and records under review, no delete.
            Role::Monitor->value => array_values(array_unique([
                ...self::for(['ViewAny', 'View', 'Create', 'Update'], ['CourtCase', 'Hearing', 'Attachment']),
            ])),

            // Analyst: read-only access to all internal data, dashboards and exports.
            Role::Analyst->value => array_values(array_unique([
                ...$readEverything,
                self::EXPORT_DATA,
                self::VIEW_ANALYTICS,
            ])),

            // Researcher: no staff-panel permissions; anonymized data via /research and the API only.
            Role::Researcher->value => [],
        ];
    }
}
