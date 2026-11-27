<?php

namespace App\Services\Documents;

/**
 * A plain document — title, details, headings, text and tables — that DocumentRenderer writes as Word (.docx) or
 * PDF. Text is escaped by the renderer; html() takes the rich text of the editor and keeps only simple markup.
 */
final class Document
{
    /** @var array<string, string> */
    public array $meta = [];

    /** @var list<array<string, mixed>> */
    public array $blocks = [];

    public ?string $notice = null;

    public function __construct(
        public string $title,
        public ?string $subtitle = null,
    ) {}

    public function meta(string $label, ?string $value): self
    {
        if (filled($value)) {
            $this->meta[$label] = (string) $value;
        }

        return $this;
    }

    /**
     * A line printed at the top in a box, e.g. "Internal: contains personal data".
     */
    public function notice(string $text): self
    {
        $this->notice = $text;

        return $this;
    }

    public function heading(string $text): self
    {
        $this->blocks[] = ['type' => 'heading', 'text' => $text];

        return $this;
    }

    public function paragraph(string $text, bool $muted = false): self
    {
        $this->blocks[] = ['type' => 'paragraph', 'text' => $text, 'muted' => $muted];

        return $this;
    }

    public function html(?string $html): self
    {
        $this->blocks[] = ['type' => 'html', 'html' => self::cleanHtml((string) $html)];

        return $this;
    }

    /**
     * @param  list<string>  $head
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function table(array $head, array $rows, ?string $empty = null): self
    {
        $this->blocks[] = ['type' => 'table', 'head' => $head, 'rows' => $rows, 'empty' => $empty];

        return $this;
    }

    /**
     * Big figures side by side: label => value.
     *
     * @param  array<string, string|int>  $figures
     */
    public function figures(array $figures): self
    {
        $this->blocks[] = ['type' => 'figures', 'figures' => $figures];

        return $this;
    }

    /**
     * A comment or quotation: who and when, then the text.
     */
    public function comment(string $byline, string $text): self
    {
        $this->blocks[] = ['type' => 'comment', 'byline' => $byline, 'text' => $text];

        return $this;
    }

    /**
     * Keeps paragraphs, line breaks, emphasis, lists, subheadings and quotations; drops every attribute (links,
     * styles, images), so nothing is fetched when the document is written and nothing unexpected is shown.
     */
    public static function cleanHtml(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><s><ul><ol><li><h2><h3><h4><blockquote>';
        $html = (string) preg_replace('#<(script|style|template|iframe|object)\b[^>]*>.*?</\1\s*>#isu', '', $html);
        $html = strip_tags($html, $allowed);
        $html = (string) preg_replace('/<(\/?)(\w+)[^>]*?(\/?)>/u', '<$1$2$3>', $html);

        // Word wants well-formed XHTML; let DOMDocument close what the editor left open.
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementsByTagName('div')->item(0);
        $out = '';

        if ($root !== null) {
            foreach ($root->childNodes as $child) {
                $out .= $dom->saveXML($child);
            }
        }

        return trim($out);
    }
}
