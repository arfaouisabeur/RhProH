<?php

$files = [
    __DIR__ . '/templates/auth/register_candidat.html.twig',
    __DIR__ . '/templates/auth/register_employe.html.twig'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // We want to replace precisely:
    // <div class="form-error-wrapper">{{ form_errors(form.FIELD) }}</div>
    // with:
    // <div style="color: red !important; font-size: 13px !important; margin-top: 5px; display: block !important; font-weight: bold;">{% for error in form.FIELD.vars.errors %}{{ error.message }}<br>{% endfor %}</div>

    $content = preg_replace_callback(
        '/<div class="form-error-wrapper">\s*\{\{\s*form_errors\((form\.[a-zA-Z0-9_]+)\)\s*\}\}\s*<\/div>/is',
        function ($matches) {
            $field = $matches[1];
            return '<div style="color: red !important; font-size: 13px !important; margin-top: 5px; display: block !important; font-weight: bold;">
              {% for error in ' . $field . '.vars.errors %}{{ error.message }}<br>{% endfor %}
            </div>';
        },
        $content
    );

    file_put_contents($file, $content);
    echo "Updated " . basename($file) . "\n";
}
