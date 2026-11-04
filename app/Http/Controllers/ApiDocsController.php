<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
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

    public function show(): View
    {
        return view('scribe.index');
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
