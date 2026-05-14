<?php

$files = [
    __DIR__ . '/templates/auth/register_candidat.html.twig',
    __DIR__ . '/templates/auth/register_employe.html.twig'
];

$css = <<<CSS
  /* Intl-Tel-Input Styling overrider */
  .iti { width: 100%; display: block; }
  .iti__flag {background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/img/flags.png");}
  @media (min-resolution: 2x) {
    .iti__flag {background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/img/flags@2x.png");}
  }
  .iti__selected-flag { padding-left: 12px; }
  .iti input[type=tel] { padding-left: 50px !important; }
CSS;

$head = <<<HTML
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
HTML;

$js = <<<JS
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.querySelector('input[type="tel"]');
    
    if (phoneInput) {
        window.intlTelInput(phoneInput, {
            initialCountry: "auto",
            geoIpLookup: function(success, failure) {
                // VPN/IP Detection via ipinfo API
                fetch("https://ipapi.co/json")
                    .then(function(res) { return res.json(); })
                    .then(function(data) { success(data.country_code); })
                    .catch(function() { success("tn"); }); // Par défaut TN
            },
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js",
            separateDialCode: true,
            preferredCountries: ["tn", "fr", "dz", "ma", "ca"]
        });
    }
});
</script>
JS;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // Insert CSS
    if (!str_contains($content, '/* Intl-Tel-Input Styling overrider */')) {
        $content = str_replace('</style>', $css . "\n</style>", $content);
    }

    // Insert Head (just before </style>)
    if (!str_contains($content, 'intlTelInput.css')) {
        $content = str_replace('<style>', $head . "\n<style>", $content);
    }

    // Insert JS (just before endblock)
    if (!str_contains($content, 'intlTelInput.min.js')) {
        $content = str_replace('{% endblock %}', $js . "\n{% endblock %}", $content);
    }

    file_put_contents($file, $content);
    echo "Updated Tel Input for: " . basename($file) . "\n";
}
