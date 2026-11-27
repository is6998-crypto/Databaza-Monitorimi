<?php

namespace App\Services\Documents;

use App\Support\Brand;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;

/**
 * Writes a Document as Word (.docx, editable) or PDF (to print or send), in the CourtWatch colours: navy headings,
 * a yellow rule under the title, navy table heads.
 */
class DocumentRenderer
{
    public function docx(Document $document): string
    {
        $word = new PhpWord;
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(10);
        $word->setDefaultParagraphStyle(['spaceAfter' => 120, 'spacing' => 40]);
        $word->getSettings()->setThemeFontLang(new Language('sq-AL'));
        $word->getDocInfo()->setTitle($document->title)->setCreator((string) config('app.name'));

        $navy = ltrim(Brand::NAVY, '#');
        $gold = ltrim(Brand::YELLOW, '#');

        $word->addTitleStyle(1, ['size' => 20, 'bold' => true, 'color' => $navy], ['spaceAfter' => 60]);
        $word->addTitleStyle(2, ['size' => 13, 'bold' => true, 'color' => $navy], ['spaceBefore' => 280, 'spaceAfter' => 100]);

        $section = $word->addSection(['marginTop' => 1000, 'marginBottom' => 1000, 'marginLeft' => 1100, 'marginRight' => 1100]);
        $section->addFooter()->addPreserveText((string) config('app.name').' · {PAGE}/{NUMPAGES}', ['size' => 8, 'color' => '6B7280'], ['alignment' => Jc::END]);

        $section->addTitle($document->title, 1);

        if ($document->subtitle !== null) {
            $section->addText($document->subtitle, ['size' => 11, 'color' => '4B5563']);
        }

        $section->addText('', [], ['borderBottomSize' => 18, 'borderBottomColor' => $gold, 'spaceAfter' => 200]);

        if ($document->notice !== null) {
            $box = $section->addTable(['borderSize' => 6, 'borderColor' => $gold, 'cellMargin' => 100]);
            $box->addRow();
            $box->addCell(9800, ['bgColor' => 'FFFAEB'])->addText($document->notice, ['bold' => true, 'size' => 9.5, 'color' => $navy]);
            $section->addTextBreak();
        }

        if ($document->meta !== []) {
            $meta = $section->addTable(['cellMargin' => 50]);

            foreach ($document->meta as $label => $value) {
                $meta->addRow();
                $meta->addCell(2800)->addText($label, ['bold' => true, 'color' => '4B5563', 'size' => 9.5]);
                $meta->addCell(7000)->addText($value, ['size' => 9.5]);
            }
        }

        foreach ($document->blocks as $block) {
            $this->docxBlock($section, $block, $navy, $gold);
        }

        $path = tempnam(sys_get_temp_dir(), 'cw-docx');
        IOFactory::createWriter($word, 'Word2007')->save((string) $path);
        $binary = (string) file_get_contents((string) $path);
        @unlink((string) $path);

        return $binary;
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function docxBlock(Section $section, array $block, string $navy, string $gold): void
    {
        switch ($block['type']) {
            case 'heading':
                $section->addTitle((string) $block['text'], 2);
                break;

            case 'paragraph':
                $section->addText((string) $block['text'], $block['muted'] ? ['color' => '6B7280', 'size' => 9.5] : []);
                break;

            case 'html':
                if ($block['html'] !== '') {
                    Html::addHtml($section, (string) $block['html']);
                }
                break;

            case 'comment':
                $section->addText((string) $block['byline'], ['bold' => true, 'size' => 9, 'color' => $navy], ['spaceBefore' => 120]);
                $section->addText((string) $block['text'], ['size' => 10], ['indentation' => ['left' => 240], 'borderLeftSize' => 12, 'borderLeftColor' => $gold]);
                break;

            case 'figures':
                $table = $section->addTable(['cellMargin' => 120]);
                $table->addRow();
                $width = (int) floor(9800 / max(1, count($block['figures'])));

                foreach ($block['figures'] as $label => $value) {
                    $cell = $table->addCell($width, ['bgColor' => 'F3F4F6']);
                    $cell->addText((string) $value, ['bold' => true, 'size' => 18, 'color' => $navy]);
                    $cell->addText((string) $label, ['size' => 8.5, 'color' => '4B5563']);
                }
                break;

            case 'table':
                if ($block['rows'] === []) {
                    $section->addText((string) ($block['empty'] ?? '—'), ['color' => '6B7280', 'size' => 9.5]);
                    break;
                }

                $table = $section->addTable(['borderSize' => 4, 'borderColor' => 'E5E7EB', 'cellMargin' => 70]);
                $table->addRow(null, ['tblHeader' => true]);
                $count = max(1, count($block['head']));

                foreach ($block['head'] as $index => $label) {
                    $table->addCell($index === 0 ? 4200 : (int) floor(5600 / max(1, $count - 1)), ['bgColor' => $navy])
                        ->addText((string) $label, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
                }

                foreach ($block['rows'] as $rowIndex => $row) {
                    $table->addRow();

                    foreach ($row as $index => $value) {
                        $table->addCell(null, $rowIndex % 2 === 1 ? ['bgColor' => 'F9FAFB'] : [])
                            ->addText((string) ($value ?? '—'), ['size' => 9], $index === 0 ? [] : ['alignment' => Jc::END]);
                    }
                }
                break;
        }
    }

    public function pdf(Document $document): string
    {
        $options = new Options;
        // Nothing outside the document is fetched (no remote images, no local files beyond the fonts).
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setDefaultFont('DejaVu Sans');
        $options->setChroot([base_path('vendor/dompdf/dompdf')]);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('documents.pdf', ['document' => $document])->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        $canvas = $pdf->getCanvas();
        $canvas->page_text(
            $canvas->get_width() - 120,
            $canvas->get_height() - 34,
            (string) config('app.name').' · {PAGE_NUM}/{PAGE_COUNT}',
            $pdf->getFontMetrics()->getFont('DejaVu Sans'),
            7.5,
            [0.42, 0.45, 0.5],
        );

        return (string) $pdf->output();
    }
}
