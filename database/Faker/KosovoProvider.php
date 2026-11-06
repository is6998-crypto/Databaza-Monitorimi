<?php

namespace Database\Faker;

use Faker\Provider\Base;

/**
 * Fake, Kosovo-flavoured data. Names are random combinations of common first names and
 * surnames — they do not refer to real judges, prosecutors, monitors or accused persons.
 */
class KosovoProvider extends Base
{
    /** @var list<string> */
    protected static array $maleFirst = ['Arben', 'Besnik', 'Driton', 'Fatmir', 'Gëzim', 'Ilir', 'Jetmir', 'Kujtim', 'Labinot', 'Mentor', 'Naim', 'Petrit', 'Rinor', 'Shkelzen', 'Valon', 'Visar', 'Arianit', 'Blerim', 'Edon', 'Florent', 'Milan', 'Nikola', 'Stefan', 'Dragan', 'Marko', 'Emir', 'Adnan', 'Kenan'];

    /** @var list<string> */
    protected static array $femaleFirst = ['Arta', 'Blerta', 'Dafina', 'Drita', 'Edona', 'Fjolla', 'Gentiana', 'Hana', 'Jehona', 'Kaltrina', 'Lirie', 'Mimoza', 'Nora', 'Qendresa', 'Rina', 'Shqipe', 'Teuta', 'Valbona', 'Vjosa', 'Zana', 'Jelena', 'Milica', 'Ana', 'Ivana', 'Amra', 'Lejla'];

    /** @var list<string> */
    protected static array $surnames = ['Berisha', 'Gashi', 'Krasniqi', 'Hoxha', 'Morina', 'Shala', 'Bytyqi', 'Kastrati', 'Rexhepi', 'Hasani', 'Kelmendi', 'Bajrami', 'Zeqiri', 'Musliu', 'Ahmeti', 'Halili', 'Thaqi', 'Sylejmani', 'Ramadani', 'Avdiu', 'Jashari', 'Mustafa', 'Hyseni', 'Llapashtica', 'Rama', 'Jovanović', 'Petrović', 'Nikolić', 'Stojanović', 'Ilić', 'Hadžić', 'Begović'];

    /** @var list<string> */
    protected static array $companySuffix = ['SH.P.K.', 'L.L.C.', 'SH.A.', 'N.T.P.'];

    /** @var list<string> */
    protected static array $companyWords = ['Ndërtimi', 'Tregtia', 'Energji', 'Agro', 'Transport', 'Dardania', 'Iliria', 'Kosova', 'Projekt', 'Invest', 'Med', 'Petrol'];

    public function kosovoFirstName(?string $gender = null): string
    {
        $gender ??= static::randomElement(['male', 'female']);

        return static::randomElement($gender === 'female' ? static::$femaleFirst : static::$maleFirst);
    }

    public function kosovoSurname(): string
    {
        return static::randomElement(static::$surnames);
    }

    public function kosovoName(?string $gender = null): string
    {
        return $this->kosovoFirstName($gender).' '.$this->kosovoSurname();
    }

    public function kosovoCompany(): string
    {
        return '"'.static::randomElement(static::$companyWords).' '.static::randomElement(static::$companyWords).'" '.static::randomElement(static::$companySuffix);
    }

    /**
     * Court case number in the style used by Kosovo courts (e.g. "PKR.nr.123/2023").
     */
    public function courtCaseNumber(string $prefix = 'P', ?int $year = null): string
    {
        $year ??= (int) $this->generator->numberBetween(2018, 2026);

        return $prefix.'.nr.'.$this->generator->numberBetween(1, 2500).'/'.$year;
    }

    public function indictmentNumber(?int $year = null): string
    {
        $year ??= (int) $this->generator->numberBetween(2017, 2026);

        return 'PP/II.nr.'.$this->generator->numberBetween(1, 3000).'/'.$year;
    }

    public function policeCaseNumber(?int $year = null): string
    {
        $year ??= (int) $this->generator->numberBetween(2016, 2026);

        return $year.'-'.static::randomElement(['AA', 'BA', 'CA', 'DA', 'EA']).'-'.$this->generator->numberBetween(100, 9999);
    }
}
