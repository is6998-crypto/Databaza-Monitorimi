<?php

use App\Enums\CaseType;
use App\Enums\CourtLevel;
use App\Enums\Role;
use App\Filament\Admin\Resources\Cases\Pages\CreateCourtCase;
use App\Models\Reference\Court;
use App\Models\Reference\CourtBranch;
use App\Models\Reference\CourtDepartment;
use App\Models\Reference\Department;
use App\Services\Reference\CourtStructure;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

function courtByCode(string $code): Court
{
    return Court::query()->where('code', $code)->firstOrFail();
}

/**
 * @return list<string>
 */
function departmentCodes(Court|CourtBranch $owner): array
{
    return $owner->departments()->orderBy('code')->pluck('code')->all();
}

it('has every court of Kosovo with its branches and chambers', function () {
    expect(Court::query()->where('level', CourtLevel::Basic->value)->count())->toBe(7)
        ->and(courtByCode('GJK')->level)->toBe(CourtLevel::Commercial)
        ->and(courtByCode('GJAD')->level)->toBe(CourtLevel::Administrative)
        ->and(courtByCode('GJK')->branches()->pluck('name_sq')->all())->toEqualCanonicalizing(['Dhoma e Shkallës së Parë', 'Dhoma e Shkallës së Dytë'])
        ->and(courtByCode('GJS')->branches()->pluck('name_sq')->all())->toBe(['Dhoma e Posaçme'])
        ->and(courtByCode('GJA')->branches()->pluck('name_sq')->all())->toBe(['Divizioni në Mitrovicë'])
        ->and(courtByCode('GJAD')->branches()->count())->toBe(0);

    $branches = fn (string $code): array => courtByCode($code)->branches()->pluck('city')->all();

    expect($branches('GJTH-PR'))->toEqualCanonicalizing(['Podujevë', 'Graçanicë', 'Lipjan', 'Gllogoc', 'Fushë Kosovë'])
        ->and($branches('GJTH-MI'))->toEqualCanonicalizing(['Leposaviq', 'Vushtrri', 'Zubin Potok', 'Skenderaj'])
        ->and($branches('GJTH-PE'))->toEqualCanonicalizing(['Istog', 'Klinë', 'Deçan'])
        ->and($branches('GJTH-PZ'))->toEqualCanonicalizing(['Dragash', 'Suharekë'])
        ->and($branches('GJTH-FE'))->toEqualCanonicalizing(['Kaçanik', 'Shtërpcë'])
        ->and($branches('GJTH-GJ'))->toEqualCanonicalizing(['Kamenicë', 'Viti', 'Novobërdë'])
        ->and($branches('GJTH-GJK'))->toEqualCanonicalizing(['Malishevë', 'Rahovec']);
});

it('gives the Special Department to Prishtina only and the General Department alone to branches', function () {
    expect(departmentCodes(courtByCode('GJTH-PR')))->toBe(['GEN', 'JUV', 'SER', 'SPE']);

    foreach (['GJTH-MI', 'GJTH-PE', 'GJTH-PZ', 'GJTH-FE', 'GJTH-GJ', 'GJTH-GJK'] as $code) {
        expect(departmentCodes(courtByCode($code)))->toBe(['GEN', 'JUV', 'SER']);
    }

    foreach (CourtBranch::query()->whereHas('court', fn ($court) => $court->where('level', CourtLevel::Basic->value))->get() as $branch) {
        expect(departmentCodes($branch))->toBe(['GEN']);
    }

    expect(Department::query()->where('code', 'GEN')->firstOrFail()->divisions()->pluck('name_sq')->all())
        ->toEqualCanonicalizing(['Divizioni Civil', 'Divizioni Penal', 'Divizioni për Kundërvajtje']);
});

it('offers only the departments of the chosen court or branch', function () {
    $prishtina = courtByCode('GJTH-PR');
    $peja = courtByCode('GJTH-PE');
    $ids = fn (array $codes): array => Department::query()->whereIn('code', $codes)->orderBy('id')->pluck('id')->all();

    expect(collect(Department::idsAvailableIn($prishtina->id))->sort()->values()->all())->toBe($ids(['GEN', 'JUV', 'SER', 'SPE']))
        ->and(collect(Department::idsAvailableIn($peja->id))->sort()->values()->all())->toBe($ids(['GEN', 'JUV', 'SER']))
        ->and(Department::idsAvailableIn($peja->id, $peja->branches()->value('id')))->toBe($ids(['GEN']))
        // A court with nothing listed is not restricted.
        ->and(Department::idsAvailableIn(courtByCode('GJA')->id))->toBeNull()
        ->and(Department::idsAvailableIn(null))->toBeNull();
});

it('filters the department on the case form and clears one the new court does not have', function () {
    $this->actingAs(userWithRole(Role::Editor));

    $prishtina = courtByCode('GJTH-PR');
    $peja = courtByCode('GJTH-PE');
    $special = Department::query()->where('code', 'SPE')->value('id');
    $general = Department::query()->where('code', 'GEN')->value('id');

    Livewire::test(CreateCourtCase::class)
        ->fillForm(['type' => CaseType::CriminalSpecial->value, 'court_id' => $prishtina->id])
        ->assertFormFieldExists('department_id', fn (Select $field): bool => array_key_exists($special, collect($field->getOptions())->flatMap(fn ($group) => is_array($group) ? $group : [])->all() + $field->getOptions()))
        ->fillForm(['department_id' => $special])
        // Peja has no Special Department: the choice is dropped.
        ->fillForm(['court_id' => $peja->id])
        ->assertFormSet(['department_id' => null])
        ->assertFormFieldExists('department_id', function (Select $field) use ($special, $general): bool {
            $options = collect($field->getOptions())->flatMap(fn ($group, $key) => is_array($group) ? $group : [$key => $group])->keys()->all();

            return in_array($general, $options, false) && ! in_array($special, $options, false);
        });
});

it('can be synced again without duplicates, renames Drenas and keeps departments edited by staff', function () {
    $prishtina = courtByCode('GJTH-PR');
    $gllogoc = $prishtina->branches()->where('name_sq', 'Dega në Gllogoc')->firstOrFail();
    $gllogoc->update(['name_sq' => 'Dega në Drenas']);

    $peja = courtByCode('GJTH-PE');
    $peja->departments()->sync(Department::query()->where('code', 'GEN')->pluck('id'));

    $courts = Court::query()->count();
    $branches = CourtBranch::query()->count();

    (new CourtStructure)->sync();

    expect(Court::query()->count())->toBe($courts)
        ->and(CourtBranch::query()->count())->toBe($branches)
        ->and($gllogoc->fresh()->name_sq)->toBe('Dega në Gllogoc')
        ->and(departmentCodes($peja->fresh()))->toBe(['GEN']);
});

it('audits a change of the departments of a court', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $court = courtByCode('GJA');

    $court->departments()->attach(Department::query()->where('code', 'SER')->value('id'));

    expect(Activity::query()->where('log_name', 'reference')->where('event', 'created')->where('subject_type', (new CourtDepartment)->getMorphClass())->exists())->toBeTrue();

    $court->departments()->detach();

    expect(Activity::query()->where('log_name', 'reference')->where('event', 'deleted')->where('subject_type', (new CourtDepartment)->getMorphClass())->exists())->toBeTrue();
});
