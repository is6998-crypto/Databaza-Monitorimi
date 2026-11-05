<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Serves the Scribe-generated researcher API docs at /docs/api (HTML, OpenAPI, Postman).
 * The files are generated with `composer docs:api` and committed; the base URL is filled
 * in at request time so the same files work on every deployment.
 */
class ApiDocsController extends Controller
{
    private const PLACEHOLDERS = ['{{ config("app.url") }}', '{{ config(\"app.url\") }}'];

    /**
     * Libraries the generated page loads from public CDNs, served from public/vendor/scribe/lib
     * instead: visitors' browsers never contact third parties and the strict CSP applies.
     */
    private const LOCAL_ASSETS = [
        'https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js' => 'vendor/scribe/lib/lodash-4.17.10.min.js',
        'https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css' => 'vendor/scribe/lib/highlight-obsidian-11.6.0.min.css',
        'https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js' => 'vendor/scribe/lib/highlight-11.6.0.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js' => 'vendor/scribe/lib/jets-0.14.1.min.js',
    ];

    public function show(): Response
    {
        $html = view('scribe.index')->render();
        $html = str_replace(array_keys(self::LOCAL_ASSETS), array_map(fn (string $path): string => asset($path), self::LOCAL_ASSETS), $html);

        // The Open Sans web font from Google is dropped; the theme falls back to system fonts.
        $html = (string) preg_replace('#<link[^>]+fonts\.googleapis\.com[^>]*>#', '', $html);

        return response($html);
    }

    public function openapi(): Response
    {
        return response($this->read('scribe/openapi.yaml'), 200, ['Content-Type' => 'application/yaml; charset=utf-8']);
    }

    public function postman(): Response
    {
        return response($this->read('scribe/collection.json'), 200, ['Content-Type' => 'application/json']);
    }

    private function read(string $path): string
    {
        $contents = Storage::disk('local')->get($path);
        abort_if($contents === null, 404);

        return str_replace(self::PLACEHOLDERS, rtrim((string) config('app.url'), '/'), $contents);
    }
}
