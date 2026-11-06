<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>CourtWatch Researcher API</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
                    body .content .python-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.11.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.11.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;,&quot;python&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                                            <button type="button" class="lang-button" data-language-name="python">python</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-cases" class="tocify-header">
                <li class="tocify-item level-1" data-unique="cases">
                    <a href="#cases">Cases</a>
                </li>
                                    <ul id="tocify-subheader-cases" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="cases-GETapi-v1-cases">
                                <a href="#cases-GETapi-v1-cases">List cases</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="cases-GETapi-v1-cases--ref-">
                                <a href="#cases-GETapi-v1-cases--ref-">Show a case</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-parties" class="tocify-header">
                <li class="tocify-item level-1" data-unique="parties">
                    <a href="#parties">Parties</a>
                </li>
                                    <ul id="tocify-subheader-parties" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="parties-GETapi-v1-parties">
                                <a href="#parties-GETapi-v1-parties">List parties</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-hearings" class="tocify-header">
                <li class="tocify-item level-1" data-unique="hearings">
                    <a href="#hearings">Hearings</a>
                </li>
                                    <ul id="tocify-subheader-hearings" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="hearings-GETapi-v1-hearings">
                                <a href="#hearings-GETapi-v1-hearings">List hearings</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-statistics" class="tocify-header">
                <li class="tocify-item level-1" data-unique="statistics">
                    <a href="#statistics">Statistics</a>
                </li>
                                    <ul id="tocify-subheader-statistics" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="statistics-GETapi-v1-statistics">
                                <a href="#statistics-GETapi-v1-statistics">Aggregate statistics</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-reference-data" class="tocify-header">
                <li class="tocify-item level-1" data-unique="reference-data">
                    <a href="#reference-data">Reference data</a>
                </li>
                                    <ul id="tocify-subheader-reference-data" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="reference-data-GETapi-v1-reference-courts">
                                <a href="#reference-data-GETapi-v1-reference-courts">Courts</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="reference-data-GETapi-v1-reference-offences">
                                <a href="#reference-data-GETapi-v1-reference-offences">Criminal offences</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="reference-data-GETapi-v1-reference-codes">
                                <a href="#reference-data-GETapi-v1-reference-codes">Code lists (enumerations)</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: 05.11.2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>Read-only API with anonymized, verified court-monitoring data from Kosovo, for approved researchers.</p>
<aside>
    <strong>Base URL</strong>: <code>{{ config("app.url") }}</code>
</aside>
<p>The researcher API gives approved researchers read-only access to <strong>anonymized</strong> record-level data
(cases, parties, hearings) and to the same suppressed aggregate statistics as the public portal.</p>
<p><strong>Who can use it.</strong> Register in the researcher area (<code>/research/register</code>). After an administrator approves
the account and you accept the data-use agreement, create a token under <strong>API tokens</strong> (<code>/research/api-tokens</code>)
and send it in the <code>Authorization: Bearer &lt;token&gt;</code> header. Tokens carry the <code>research:read</code> ability and expire.</p>
<p><strong>What you get.</strong> Only verified (approved or published) records. Never names, case numbers, judges,
prosecutors, monitors or free-text notes. Cases and hearings are identified by stable references (ULIDs),
parties by stable pseudonyms. Aggregate values under 5 are <code>null</code> (small-cell suppression).</p>
<p><strong>Conventions.</strong> Lists are paginated (<code>page</code>, <code>per_page</code> up to 100). Dates are <code>Y-m-d</code>. Labels follow <code>?lang=sq|en</code>
or the <code>Accept-Language</code> header (Albanian by default); codes never change. Requests are rate limited
(60 per minute by default; <code>429</code> when exceeded). Every request that returns records is logged.</p>
<aside>Errors: <code>401</code> missing or invalid token, <code>403</code> account not approved, agreement not accepted
or token without the <code>research:read</code> ability, <code>422</code> invalid filter, <code>429</code> too many requests.</aside>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>To authenticate requests, include an <strong><code>Authorization</code></strong> header with the value <strong><code>"Bearer {YOUR_API_TOKEN}"</code></strong>.</p>
<p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation below.</p>
<p>Create a token in the researcher area under <b>API tokens</b> (<code>/research/api-tokens</code>). Only approved researchers who accepted the data-use agreement can create one.</p>

        <h1 id="cases">Cases</h1>

    <p>Anonymized, verified (approved or published) monitored cases.</p>

                                <h2 id="cases-GETapi-v1-cases">List cases</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-cases">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/cases?type=criminal_corruption&amp;court_id=1&amp;year=2024&amp;per_page=25&amp;page=1&amp;lang=en" \
    --header "Authorization: Bearer {YOUR_API_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/cases"
);

const params = {
    "type": "criminal_corruption",
    "court_id": "1",
    "year": "2024",
    "per_page": "25",
    "page": "1",
    "lang": "en",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/cases'
params = {
  'type': 'criminal_corruption',
  'court_id': '1',
  'year': '2024',
  'per_page': '25',
  'page': '1',
  'lang': 'en',
}
headers = {
  'Authorization': 'Bearer {YOUR_API_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-cases">
            <blockquote>
            <p>Example response (200, Success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;ref&quot;: &quot;01JD8Z6Y5P4S7T6W2Q9R1M3K5N&quot;,
            &quot;code&quot;: &quot;CW-Q9R1M3K5N&quot;,
            &quot;type&quot;: {
                &quot;value&quot;: &quot;criminal_corruption&quot;,
                &quot;label&quot;: &quot;Criminal &ndash; Corruption&quot;
            },
            &quot;court&quot;: {
                &quot;id&quot;: 1,
                &quot;label&quot;: &quot;Basic Court of Prishtina&quot;,
                &quot;level&quot;: &quot;basic&quot;
            },
            &quot;department&quot;: &quot;Serious Crimes Department&quot;,
            &quot;status&quot;: &quot;approved&quot;,
            &quot;parties&quot;: {
                &quot;total&quot;: 3,
                &quot;accused&quot;: 2
            },
            &quot;stages&quot;: [
                {
                    &quot;stage&quot;: &quot;criminal_report_filed&quot;,
                    &quot;label&quot;: &quot;Criminal report filed&quot;,
                    &quot;date&quot;: &quot;2023-01-10&quot;,
                    &quot;outcome&quot;: null,
                    &quot;is_direct_indictment&quot;: false,
                    &quot;appellant&quot;: null
                }
            ],
            &quot;offences&quot;: [
                {
                    &quot;stage&quot;: &quot;indictment&quot;,
                    &quot;code&quot;: &quot;KPRK-2019&quot;,
                    &quot;article&quot;: &quot;414&quot;,
                    &quot;title&quot;: &quot;KPRK-2019 art. 414 &mdash; Abuse of official position or authority&quot;,
                    &quot;category&quot;: &quot;Corruption&quot;
                }
            ],
            &quot;hearings&quot;: {
                &quot;total&quot;: 5,
                &quot;postponed&quot;: 2
            }
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;...&quot;,
        &quot;last&quot;: &quot;...&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: &quot;...&quot;
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;per_page&quot;: 25,
        &quot;total&quot;: 120
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-cases" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-cases"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-cases"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-cases" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-cases">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-cases" data-method="GET"
      data-path="api/v1/cases"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-cases', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-cases"
                    onclick="tryItOut('GETapi-v1-cases');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-cases"
                    onclick="cancelTryOut('GETapi-v1-cases');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-cases"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/cases</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-cases"
               value="Bearer {YOUR_API_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_API_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-cases"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-cases"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="GETapi-v1-cases"
               value="criminal_corruption"
               data-component="query">
    <br>
<p>Case type (e.g. criminal_corruption, civil). Example: <code>criminal_corruption</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>court_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="court_id"                data-endpoint="GETapi-v1-cases"
               value="1"
               data-component="query">
    <br>
<p>Court id (see /reference/courts). Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>year</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="year"                data-endpoint="GETapi-v1-cases"
               value="2024"
               data-component="query">
    <br>
<p>Year in which any stage took place. Example: <code>2024</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-cases"
               value="25"
               data-component="query">
    <br>
<p>Items per page (max 100). Example: <code>25</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-cases"
               value="1"
               data-component="query">
    <br>
<p>Page number. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-cases"
               value="en"
               data-component="query">
    <br>
<p>Language of the labels: sq (default) or en. Example: <code>en</code></p>
            </div>
                </form>

                    <h2 id="cases-GETapi-v1-cases--ref-">Show a case</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-cases--ref-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/cases/01JD8Z6Y5P4S7T6W2Q9R1M3K5N?lang=en" \
    --header "Authorization: Bearer {YOUR_API_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/cases/01JD8Z6Y5P4S7T6W2Q9R1M3K5N"
);

const params = {
    "lang": "en",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/cases/01JD8Z6Y5P4S7T6W2Q9R1M3K5N'
params = {
  'lang': 'en',
}
headers = {
  'Authorization': 'Bearer {YOUR_API_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-cases--ref-">
            <blockquote>
            <p>Example response (200, Success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: {
        &quot;ref&quot;: &quot;01JD8Z6Y5P4S7T6W2Q9R1M3K5N&quot;,
        &quot;code&quot;: &quot;CW-Q9R1M3K5N&quot;,
        &quot;type&quot;: {
            &quot;value&quot;: &quot;criminal_corruption&quot;,
            &quot;label&quot;: &quot;Criminal &ndash; Corruption&quot;
        },
        &quot;court&quot;: {
            &quot;id&quot;: 1,
            &quot;label&quot;: &quot;Basic Court of Prishtina&quot;,
            &quot;level&quot;: &quot;basic&quot;
        },
        &quot;department&quot;: &quot;Serious Crimes Department&quot;,
        &quot;status&quot;: &quot;published&quot;,
        &quot;parties&quot;: {
            &quot;total&quot;: 3,
            &quot;accused&quot;: 2
        },
        &quot;stages&quot;: [
            {
                &quot;stage&quot;: &quot;indictment_filed&quot;,
                &quot;label&quot;: &quot;Indictment filed&quot;,
                &quot;date&quot;: &quot;2023-05-02&quot;,
                &quot;outcome&quot;: null,
                &quot;is_direct_indictment&quot;: false,
                &quot;appellant&quot;: null
            }
        ],
        &quot;offences&quot;: [
            {
                &quot;stage&quot;: &quot;indictment&quot;,
                &quot;code&quot;: &quot;KPRK-2019&quot;,
                &quot;article&quot;: &quot;414&quot;,
                &quot;title&quot;: &quot;KPRK-2019 art. 414 &mdash; Abuse of official position or authority&quot;,
                &quot;category&quot;: &quot;Corruption&quot;
            }
        ],
        &quot;hearings&quot;: {
            &quot;total&quot;: 5,
            &quot;postponed&quot;: 2
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, Unknown or unverified case):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Not Found&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-cases--ref-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-cases--ref-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-cases--ref-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-cases--ref-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-cases--ref-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-cases--ref-" data-method="GET"
      data-path="api/v1/cases/{ref}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-cases--ref-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-cases--ref-"
                    onclick="tryItOut('GETapi-v1-cases--ref-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-cases--ref-"
                    onclick="cancelTryOut('GETapi-v1-cases--ref-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-cases--ref-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/cases/{ref}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-cases--ref-"
               value="Bearer {YOUR_API_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_API_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-cases--ref-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-cases--ref-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>ref</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ref"                data-endpoint="GETapi-v1-cases--ref-"
               value="01JD8Z6Y5P4S7T6W2Q9R1M3K5N"
               data-component="url">
    <br>
<p>The case reference (ULID) from the list. Example: <code>01JD8Z6Y5P4S7T6W2Q9R1M3K5N</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-cases--ref-"
               value="en"
               data-component="query">
    <br>
<p>Language of the labels: sq (default) or en. Example: <code>en</code></p>
            </div>
                </form>

                <h1 id="parties">Parties</h1>

    <p>Anonymized parties of verified cases: pseudonymous id, role, gender, nationality,
economic status, position held, counsel, charges and sentences. Never names.</p>

                                <h2 id="parties-GETapi-v1-parties">List parties</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-parties">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/parties?type=criminal_corruption&amp;court_id=1&amp;per_page=50&amp;page=1&amp;lang=en" \
    --header "Authorization: Bearer {YOUR_API_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/parties"
);

const params = {
    "type": "criminal_corruption",
    "court_id": "1",
    "per_page": "50",
    "page": "1",
    "lang": "en",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/parties'
params = {
  'type': 'criminal_corruption',
  'court_id': '1',
  'per_page': '50',
  'page': '1',
  'lang': 'en',
}
headers = {
  'Authorization': 'Bearer {YOUR_API_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-parties">
            <blockquote>
            <p>Example response (200, Success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: &quot;P-3f9a1c0b2e4d&quot;,
            &quot;case_ref&quot;: &quot;01JD8Z6Y5P4S7T6W2Q9R1M3K5N&quot;,
            &quot;case_type&quot;: &quot;criminal_corruption&quot;,
            &quot;role&quot;: &quot;accused&quot;,
            &quot;gender&quot;: &quot;male&quot;,
            &quot;nationality&quot;: &quot;Albanian&quot;,
            &quot;economic_status&quot;: &quot;Employed&quot;,
            &quot;position_held&quot;: &quot;Mayor&quot;,
            &quot;counsel_type&quot;: &quot;retained&quot;,
            &quot;counsel_at_police&quot;: true,
            &quot;charges&quot;: [
                {
                    &quot;stage&quot;: &quot;indictment&quot;,
                    &quot;code&quot;: &quot;KPRK-2019&quot;,
                    &quot;article&quot;: &quot;414&quot;,
                    &quot;category&quot;: &quot;Corruption&quot;
                }
            ],
            &quot;sentences&quot;: [
                {
                    &quot;kind&quot;: &quot;imposed&quot;,
                    &quot;instance&quot;: &quot;first_instance&quot;,
                    &quot;prison_months&quot;: 24,
                    &quot;suspended_months&quot;: 0,
                    &quot;fine_eur&quot;: 1000
                }
            ]
        }
    ],
    &quot;links&quot;: {},
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;per_page&quot;: 50,
        &quot;total&quot;: 300
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-parties" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-parties"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-parties"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-parties" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-parties">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-parties" data-method="GET"
      data-path="api/v1/parties"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-parties', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-parties"
                    onclick="tryItOut('GETapi-v1-parties');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-parties"
                    onclick="cancelTryOut('GETapi-v1-parties');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-parties"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/parties</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-parties"
               value="Bearer {YOUR_API_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_API_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-parties"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-parties"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="GETapi-v1-parties"
               value="criminal_corruption"
               data-component="query">
    <br>
<p>Case type. Example: <code>criminal_corruption</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>court_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="court_id"                data-endpoint="GETapi-v1-parties"
               value="1"
               data-component="query">
    <br>
<p>Court id. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-parties"
               value="50"
               data-component="query">
    <br>
<p>Items per page (max 100). Example: <code>50</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-parties"
               value="1"
               data-component="query">
    <br>
<p>Page number. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-parties"
               value="en"
               data-component="query">
    <br>
<p>Language of the labels: sq (default) or en. Example: <code>en</code></p>
            </div>
                </form>

                <h1 id="hearings">Hearings</h1>

    <p>Anonymized, verified monitored hearings (structured answers only).</p>

                                <h2 id="hearings-GETapi-v1-hearings">List hearings</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-hearings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/hearings?type=criminal_general&amp;court_id=1&amp;from=2024-01-01&amp;until=2024-12-31&amp;per_page=50&amp;page=1&amp;lang=en" \
    --header "Authorization: Bearer {YOUR_API_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/hearings"
);

const params = {
    "type": "criminal_general",
    "court_id": "1",
    "from": "2024-01-01",
    "until": "2024-12-31",
    "per_page": "50",
    "page": "1",
    "lang": "en",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/hearings'
params = {
  'type': 'criminal_general',
  'court_id': '1',
  'from': '2024-01-01',
  'until': '2024-12-31',
  'per_page': '50',
  'page': '1',
  'lang': 'en',
}
headers = {
  'Authorization': 'Bearer {YOUR_API_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-hearings">
            <blockquote>
            <p>Example response (200, Success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;ref&quot;: &quot;01JD8Z6Y5P4S7T6W2Q9R1M3K5N&quot;,
            &quot;case_ref&quot;: &quot;01JD8Z6Y5P4S7T6W2Q9R1M3K5A&quot;,
            &quot;case_type&quot;: &quot;criminal_corruption&quot;,
            &quot;court&quot;: {
                &quot;id&quot;: 1,
                &quot;label&quot;: &quot;Basic Court of Prishtina&quot;
            },
            &quot;date&quot;: &quot;2024-03-12&quot;,
            &quot;hearing_type&quot;: &quot;main_trial&quot;,
            &quot;venue&quot;: &quot;courtroom&quot;,
            &quot;is_public&quot;: true,
            &quot;started_late&quot;: true,
            &quot;delay_minutes&quot;: 25,
            &quot;delay_cause&quot;: &quot;panel&quot;,
            &quot;was_postponed&quot;: true,
            &quot;postponement_reasons&quot;: [
                &quot;defence_lawyer&quot;
            ],
            &quot;recording&quot;: &quot;audio&quot;,
            &quot;interpretation_needed&quot;: false,
            &quot;interpretation_provided&quot;: null,
            &quot;phone_used&quot;: false,
            &quot;phone_users&quot;: [],
            &quot;security_adequate&quot;: true,
            &quot;participants&quot;: [
                &quot;judge&quot;,
                &quot;prosecutor&quot;,
                &quot;accused&quot;
            ]
        }
    ],
    &quot;links&quot;: {},
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;per_page&quot;: 50,
        &quot;total&quot;: 700
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-hearings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-hearings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-hearings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-hearings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-hearings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-hearings" data-method="GET"
      data-path="api/v1/hearings"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-hearings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-hearings"
                    onclick="tryItOut('GETapi-v1-hearings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-hearings"
                    onclick="cancelTryOut('GETapi-v1-hearings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-hearings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/hearings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-hearings"
               value="Bearer {YOUR_API_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_API_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-hearings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-hearings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="GETapi-v1-hearings"
               value="criminal_general"
               data-component="query">
    <br>
<p>Case type. Example: <code>criminal_general</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>court_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="court_id"                data-endpoint="GETapi-v1-hearings"
               value="1"
               data-component="query">
    <br>
<p>Court id. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="from"                data-endpoint="GETapi-v1-hearings"
               value="2024-01-01"
               data-component="query">
    <br>
<p>Hearings on or after this date (Y-m-d). Example: <code>2024-01-01</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>until</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="until"                data-endpoint="GETapi-v1-hearings"
               value="2024-12-31"
               data-component="query">
    <br>
<p>Hearings on or before this date (Y-m-d). Example: <code>2024-12-31</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-hearings"
               value="50"
               data-component="query">
    <br>
<p>Items per page (max 100). Example: <code>50</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-hearings"
               value="1"
               data-component="query">
    <br>
<p>Page number. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-hearings"
               value="en"
               data-component="query">
    <br>
<p>Language of the labels: sq (default) or en. Example: <code>en</code></p>
            </div>
                </form>

                <h1 id="statistics">Statistics</h1>

    <p>Aggregate indicators (the same suppressed figures as the public portal: values under 5 are null).</p>

                                <h2 id="statistics-GETapi-v1-statistics">Aggregate statistics</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-statistics">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/statistics?from=2024-01-01&amp;until=2024-12-31&amp;court_id=1&amp;case_type=criminal_corruption&amp;lang=en" \
    --header "Authorization: Bearer {YOUR_API_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/statistics"
);

const params = {
    "from": "2024-01-01",
    "until": "2024-12-31",
    "court_id": "1",
    "case_type": "criminal_corruption",
    "lang": "en",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_API_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/statistics'
params = {
  'from': '2024-01-01',
  'until': '2024-12-31',
  'court_id': '1',
  'case_type': 'criminal_corruption',
  'lang': 'en',
}
headers = {
  'Authorization': 'Bearer {YOUR_API_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers, params=params)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-statistics">
            <blockquote>
            <p>Example response (200, Success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;headline&quot;: {
        &quot;cases&quot;: 156,
        &quot;hearings&quot;: 507,
        &quot;postponement_rate&quot;: 31,
        &quot;late_rate&quot;: 29.4,
        &quot;public_rate&quot;: 83,
        &quot;conviction_rate&quot;: 55.9
    },
    &quot;hearings_by_year&quot;: {
        &quot;2024&quot;: 99,
        &quot;2025&quot;: 119
    },
    &quot;cases_by_type&quot;: {
        &quot;Criminal &ndash; Corruption&quot;: 55
    },
    &quot;postponement_by_court&quot;: [
        {
            &quot;court&quot;: &quot;Basic Court of Prishtina&quot;,
            &quot;hearings&quot;: 225,
            &quot;postponed&quot;: 63,
            &quot;rate&quot;: 28
        }
    ],
    &quot;postponement_reasons&quot;: {
        &quot;Defence lawyer&quot;: 61
    },
    &quot;verdicts&quot;: {
        &quot;Convicting&quot;: 33
    },
    &quot;stage_durations&quot;: [
        {
            &quot;label&quot;: &quot;Criminal report filed &rarr; Indictment filed&quot;,
            &quot;median&quot;: 329,
            &quot;cases&quot;: 103
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-statistics" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-statistics"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-statistics"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-statistics" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-statistics">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-statistics" data-method="GET"
      data-path="api/v1/statistics"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-statistics', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-statistics"
                    onclick="tryItOut('GETapi-v1-statistics');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-statistics"
                    onclick="cancelTryOut('GETapi-v1-statistics');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-statistics"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/statistics</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-statistics"
               value="Bearer {YOUR_API_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_API_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-statistics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-statistics"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="from"                data-endpoint="GETapi-v1-statistics"
               value="2024-01-01"
               data-component="query">
    <br>
<p>Start date (Y-m-d). Example: <code>2024-01-01</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>until</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="until"                data-endpoint="GETapi-v1-statistics"
               value="2024-12-31"
               data-component="query">
    <br>
<p>End date (Y-m-d). Example: <code>2024-12-31</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>court_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="court_id"                data-endpoint="GETapi-v1-statistics"
               value="1"
               data-component="query">
    <br>
<p>Court id. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>case_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="case_type"                data-endpoint="GETapi-v1-statistics"
               value="criminal_corruption"
               data-component="query">
    <br>
<p>Case type. Example: <code>criminal_corruption</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="lang"                data-endpoint="GETapi-v1-statistics"
               value="en"
               data-component="query">
    <br>
<p>Language of the labels: sq (default) or en. Example: <code>en</code></p>
            </div>
                </form>

                <h1 id="reference-data">Reference data</h1>

    <p>Code lists used by the other endpoints (no personal data: judges and prosecutors are not exposed).</p>

                                <h2 id="reference-data-GETapi-v1-reference-courts">Courts</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-reference-courts">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/reference/courts" \
    --header "Authorization: Bearer {YOUR_API_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/reference/courts"
);

const headers = {
    "Authorization": "Bearer {YOUR_API_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/reference/courts'
headers = {
  'Authorization': 'Bearer {YOUR_API_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-reference-courts">
            <blockquote>
            <p>Example response (200, Success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;code&quot;: &quot;GJTH-PR&quot;,
            &quot;label&quot;: &quot;Basic Court of Prishtina&quot;,
            &quot;level&quot;: &quot;basic&quot;,
            &quot;city&quot;: &quot;Prishtina&quot;
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-reference-courts" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-reference-courts"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-reference-courts"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-reference-courts" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-reference-courts">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-reference-courts" data-method="GET"
      data-path="api/v1/reference/courts"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-reference-courts', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-reference-courts"
                    onclick="tryItOut('GETapi-v1-reference-courts');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-reference-courts"
                    onclick="cancelTryOut('GETapi-v1-reference-courts');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-reference-courts"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/reference/courts</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-reference-courts"
               value="Bearer {YOUR_API_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_API_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-reference-courts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-reference-courts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="reference-data-GETapi-v1-reference-offences">Criminal offences</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-reference-offences">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/reference/offences" \
    --header "Authorization: Bearer {YOUR_API_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/reference/offences"
);

const headers = {
    "Authorization": "Bearer {YOUR_API_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/reference/offences'
headers = {
  'Authorization': 'Bearer {YOUR_API_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-reference-offences">
            <blockquote>
            <p>Example response (200, Success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;code&quot;: &quot;KPRK-2019&quot;,
            &quot;article&quot;: &quot;414&quot;,
            &quot;title&quot;: &quot;Abuse of official position or authority&quot;,
            &quot;category&quot;: &quot;Corruption&quot;
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-reference-offences" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-reference-offences"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-reference-offences"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-reference-offences" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-reference-offences">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-reference-offences" data-method="GET"
      data-path="api/v1/reference/offences"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-reference-offences', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-reference-offences"
                    onclick="tryItOut('GETapi-v1-reference-offences');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-reference-offences"
                    onclick="cancelTryOut('GETapi-v1-reference-offences');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-reference-offences"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/reference/offences</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-reference-offences"
               value="Bearer {YOUR_API_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_API_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-reference-offences"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-reference-offences"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="reference-data-GETapi-v1-reference-codes">Code lists (enumerations)</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-reference-codes">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/v1/reference/codes" \
    --header "Authorization: Bearer {YOUR_API_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/v1/reference/codes"
);

const headers = {
    "Authorization": "Bearer {YOUR_API_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>


<div class="python-example">
    <pre><code class="language-python">import requests
import json

url = 'http://localhost:8000/api/v1/reference/codes'
headers = {
  'Authorization': 'Bearer {YOUR_API_TOKEN}',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}

response = requests.request('GET', url, headers=headers)
response.json()</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-reference-codes">
            <blockquote>
            <p>Example response (200, Success):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;case_types&quot;: {
        &quot;criminal_corruption&quot;: &quot;Criminal &ndash; Corruption&quot;
    },
    &quot;stage_types&quot;: {
        &quot;criminal_report_filed&quot;: &quot;Criminal report filed&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-reference-codes" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-reference-codes"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-reference-codes"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-reference-codes" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-reference-codes">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-reference-codes" data-method="GET"
      data-path="api/v1/reference/codes"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-reference-codes', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-reference-codes"
                    onclick="tryItOut('GETapi-v1-reference-codes');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-reference-codes"
                    onclick="cancelTryOut('GETapi-v1-reference-codes');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-reference-codes"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/reference/codes</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-reference-codes"
               value="Bearer {YOUR_API_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_API_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-reference-codes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-reference-codes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                                                        <button type="button" class="lang-button" data-language-name="python">python</button>
                            </div>
            </div>
</div>
</body>
</html>
