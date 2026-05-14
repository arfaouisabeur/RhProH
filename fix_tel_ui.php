<?php
$files = [
    __DIR__ . '/templates/auth/register_candidat.html.twig',
    __DIR__ . '/templates/auth/register_employe.html.twig'
];

$newCss = <<<CSS
  /* Intl-Tel-Input Styling overrider */
  .iti { 
    display: flex !important; 
    align-items: center;
    gap: 8px; 
    width: 100%; 
  }
  .iti__flag-container { 
    position: static !important; 
    display: flex !important;
    align-items: center;
    border: 1.5px solid rgba(91,43,130,0.15) !important;
    border-radius: 11px !important;
    background: var(--white) !important;
    padding: 0 12px !important;
    height: 45px; /* Match form control height */
  }
  .iti__selected-flag { 
    padding: 0 !important; 
    background: transparent !important;
  }
  .iti input[type=tel] { 
    padding-left: 16px !important; 
    flex: 1;
  }
  
  .iti__flag {background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/img/flags.png");}
  @media (min-resolution: 2x) {
    .iti__flag {background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/img/flags@2x.png");}
  }
CSS;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Replace the old CSS block
    $pattern = '/\/\* Intl-Tel-Input Styling overrider \*\/(.*?)\.iti input\[type=tel\] \{ padding-left: \d+px !important; \}/s';
    
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $newCss, $content);
        file_put_contents($file, $content);
        echo "Refactored UI for: ".basename($file)."\n";
    } else {
        echo "Could not find old CSS block in ".basename($file)."\n";
    }
}
