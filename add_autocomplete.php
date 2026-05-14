<?php

$files = [
    __DIR__ . '/templates/auth/register_candidat.html.twig',
    __DIR__ . '/templates/auth/register_employe.html.twig'
];

$css = <<<CSS
  /* Autocomplete Styling */
  .autocomplete-wrapper { position: relative; }
  .autocomplete-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 9999;
    background: #fff;
    border: 1px solid rgba(91,43,130,0.15);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    max-height: 200px;
    overflow-y: auto;
    margin: 0;
    padding: 0;
    list-style: none;
    display: none;
  }
  .autocomplete-list li {
    padding: 10px 14px;
    cursor: pointer;
    font-size: 13px;
    color: var(--text-dark);
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: background 0.1s;
  }
  .autocomplete-list li:last-child { border-bottom: none; }
  .autocomplete-list li:hover { background: #f7f4f0; }
CSS;

$js = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addressInput = document.querySelector('input[name="registration_candidat[adresse]"]') || document.querySelector('input[name="registration_employe[adresse]"]');
    
    if (!addressInput) return;

    // Create list container
    const listWrapper = document.createElement('ul');
    listWrapper.className = 'autocomplete-list';
    addressInput.parentNode.classList.add('autocomplete-wrapper');
    addressInput.parentNode.appendChild(listWrapper);
    addressInput.setAttribute('autocomplete', 'off');

    let debounceTimer;

    addressInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 3) {
            listWrapper.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`https://photon.komoot.io/api/?q=\${encodeURIComponent(query)}&limit=5`)
                .then(r => r.json())
                .then(data => {
                    listWrapper.innerHTML = '';
                    if (data.features && data.features.length > 0) {
                        data.features.forEach(feature => {
                            const props = feature.properties;
                            // Build a clean address string
                            let addressParts = [];
                            if (props.name) addressParts.push(props.name);
                            if (props.city && props.city !== props.name) addressParts.push(props.city);
                            if (props.state) addressParts.push(props.state);
                            if (props.country) addressParts.push(props.country);
                            
                            const fullAddress = addressParts.join(', ');

                            const li = document.createElement('li');
                            li.textContent = fullAddress;
                            li.addEventListener('click', () => {
                                addressInput.value = fullAddress;
                                listWrapper.style.display = 'none';
                            });
                            listWrapper.appendChild(li);
                        });
                        listWrapper.style.display = 'block';
                    } else {
                        listWrapper.style.display = 'none';
                    }
                })
                .catch(() => listWrapper.style.display = 'none');
        }, 300);
    });

    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== addressInput && e.target !== listWrapper) {
            listWrapper.style.display = 'none';
        }
    });
});
</script>
JS;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // Insert CSS
    if (!str_contains($content, '/* Autocomplete Styling */')) {
        $content = str_replace('</style>', $css . "\n</style>", $content);
    }

    // Insert JS
    if (!str_contains($content, 'const addressInput = document.querySelector')) {
        $content = str_replace('{% endblock %}', $js . "\n{% endblock %}", $content);
    }

    file_put_contents($file, $content);
    echo "Updated Autocomplete for: " . basename($file) . "\n";
}
