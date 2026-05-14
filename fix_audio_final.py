#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import re

# Corriger les congés
file_path = 'templates/conge_tt/index.html.twig'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Corriger l'emoji du bouton
content = content.replace('ð¤', '🤖')

# Remplacer le code audio par une version qui fonctionne
pattern = r'// -- Rapport vocal.*?}\);\s*</script>'

new_code = '''// -- Rapport vocal
  var btnVoice  = document.getElementById('cgmBtnVoice');
  var btnIcon   = document.getElementById('cgmBtnIcon');
  var btnTxt    = document.getElementById('cgmBtnTxt');

  if (btnVoice && btnIcon && btnTxt) {
    btnVoice.addEventListener('click', function(){
      if (btnVoice.disabled) return;
      
      // Arrêter toute lecture en cours
      if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
      }
      
      if (!window.speechSynthesis) {
        btnTxt.textContent = 'Non supporté';
        return;
      }

      // Texte simple et propre
      var texte = 'Bonjour. Voici le rapport des congés. Au total, ' + t + ' demandes ont été enregistrées. ' + 
                  a + ' demandes ont été acceptées, ' + r + ' demandes ont été refusées, et ' + p + ' demandes sont en attente. ' +
                  'Fin du rapport. Merci.';
      
      console.log('[Audio] Texte à lire:', texte);
      
      var utter = new SpeechSynthesisUtterance(texte);
      utter.lang = 'fr-FR';
      utter.rate = 0.8;
      utter.pitch = 1;
      utter.volume = 1;
      
      utter.onstart = function() {
        console.log('[Audio] ✅ Démarré');
        btnIcon.className = 'fas fa-volume-high';
        btnTxt.textContent = '🔊 En cours...';
        btnVoice.disabled = true;
      };
      
      utter.onend = function() {
        console.log('[Audio] ✅ Terminé');
        btnIcon.className = 'fas fa-volume-high';
        btnTxt.textContent = '🤖 Rejouer';
        btnVoice.disabled = false;
      };
      
      utter.onerror = function(e) {
        console.error('[Audio] ❌ Erreur:', e.error);
        btnIcon.className = 'fas fa-exclamation-triangle';
        btnTxt.textContent = 'Erreur: ' + e.error;
        btnVoice.disabled = false;
      };
      
      btnIcon.className = 'fas fa-spinner fa-spin';
      btnTxt.textContent = 'Chargement...';
      btnVoice.disabled = true;
      
      // Attendre un peu puis lancer
      setTimeout(function() {
        window.speechSynthesis.speak(utter);
      }, 100);
    });
  }
})();
</script>'''

content = re.sub(pattern, new_code, content, flags=re.DOTALL)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Audio congés corrigé!")

# Maintenant corriger les services
service_file = 'templates/demande_service/index.html.twig'

try:
    with open(service_file, 'r', encoding='utf-8') as f:
        service_content = f.read()
    
    # Corriger l'emoji
    service_content = service_content.replace('ð¤', '🤖')
    
    # Chercher et corriger le code audio des services
    service_pattern = r'// -- Rapport vocal.*?}\);\s*</script>'
    
    service_new_code = '''// -- Rapport vocal
  var btnVoice  = document.getElementById('btnVocalService');
  var btnIcon   = document.getElementById('btnVocalIcon');
  var btnTxt    = document.getElementById('btnVocalTxt');

  if (btnVoice && btnIcon && btnTxt) {
    btnVoice.addEventListener('click', function(){
      if (btnVoice.disabled) return;
      
      if (window.speechSynthesis) {
        window.speechSynthesis.cancel();
      }
      
      if (!window.speechSynthesis) {
        btnTxt.textContent = 'Non supporté';
        return;
      }

      var texte = 'Bonjour. Voici le rapport des services. Au total, ' + totalServices + ' demandes de service ont été enregistrées. ' +
                  'Fin du rapport. Merci.';
      
      console.log('[Audio Services] Texte:', texte);
      
      var utter = new SpeechSynthesisUtterance(texte);
      utter.lang = 'fr-FR';
      utter.rate = 0.8;
      utter.pitch = 1;
      utter.volume = 1;
      
      utter.onstart = function() {
        btnIcon.className = 'fas fa-volume-high';
        btnTxt.textContent = '🔊 En cours...';
        btnVoice.disabled = true;
      };
      
      utter.onend = function() {
        btnIcon.className = 'fas fa-volume-high';
        btnTxt.textContent = '🤖 Rejouer';
        btnVoice.disabled = false;
      };
      
      utter.onerror = function(e) {
        console.error('[Audio Services] Erreur:', e.error);
        btnIcon.className = 'fas fa-exclamation-triangle';
        btnTxt.textContent = 'Erreur: ' + e.error;
        btnVoice.disabled = false;
      };
      
      btnIcon.className = 'fas fa-spinner fa-spin';
      btnTxt.textContent = 'Chargement...';
      btnVoice.disabled = true;
      
      setTimeout(function() {
        window.speechSynthesis.speak(utter);
      }, 100);
    });
  }
})();
</script>'''
    
    service_content = re.sub(service_pattern, service_new_code, service_content, flags=re.DOTALL)
    
    with open(service_file, 'w', encoding='utf-8') as f:
        f.write(service_content)
    
    print("✅ Audio services corrigé!")
    
except FileNotFoundError:
    print("⚠️ Fichier services non trouvé")

print("✅ Correction audio terminée!")