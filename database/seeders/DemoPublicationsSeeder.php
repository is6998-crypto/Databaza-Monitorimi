<?php

namespace Database\Seeders;

use App\Enums\PublicationType;
use App\Models\Publication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo publications with placeholder PDFs (development only).
 */
class DemoPublicationsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['raporti-vjetor-2025-demo', PublicationType::Report, 'Raporti vjetor i monitorimit 2025 (demo)', 'Annual monitoring report 2025 (demo)', 'Gjetjet kryesore nga monitorimi i seancave gjyqësore gjatë vitit 2025: shtyrjet, përpikëria dhe transparenca. Ky është një dokument demonstrues me të dhëna fiktive.', 'Key findings from monitoring court hearings in 2025: postponements, punctuality and transparency. This is a demonstration document with fake data.', '2026-03-15'],
            ['shtyrjet-e-seancave-demo', PublicationType::Brief, 'Pse shtyhen seancat? (demo)', 'Why are hearings postponed? (demo)', 'Përmbledhje politikash për arsyet e shtyrjes së seancave në lëndët e korrupsionit. Dokument demonstrues.', 'Policy brief on the reasons hearings are postponed in corruption cases. Demonstration document.', '2026-06-01'],
            ['te-dhenat-e-hapura-2026-demo', PublicationType::Dataset, 'Të dhëna të hapura 2026 (demo)', 'Open data 2026 (demo)', 'Përshkrimi i seteve të të dhënave të hapura dhe i metodologjisë së anonimizimit.', 'Description of the open datasets and the anonymisation methodology.', '2026-09-20'],
        ];

        foreach ($items as [$slug, $type, $titleSq, $titleEn, $summarySq, $summaryEn, $date]) {
            $path = "publications/{$slug}.pdf";
            Storage::disk('local')->put($path, self::pdf($titleEn));

            Publication::query()->updateOrCreate(['slug' => $slug], [
                'type' => $type,
                'title_sq' => $titleSq,
                'title_en' => $titleEn,
                'summary_sq' => $summarySq,
                'summary_en' => $summaryEn,
                'published_on' => $date,
                'is_published' => true,
                'file_disk' => 'local',
                'file_path' => $path,
            ]);
        }
    }

    /**
     * A minimal one-page PDF with a title (placeholder for real reports).
     */
    private static function pdf(string $title): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $title.' - fake demonstration data');
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
