#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import re
import os

def fix_web_speech_api():
    """Fix Web Speech API synthesis-failed errors across all templates"""
    
    print("🔧 Fixing Web Speech API synthesis-failed errors...")
    
    # Fix conge_tt template
    conge_file = 'templates/conge_tt/index.html.twig'
    if os.path.exists(conge_file):
        print(f"📝 Fixing {conge_file}...")
        
        with open(conge_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Fix corrupted emojis
        content = content.replace('ð¤', '🤖')
        content = content.replace('-¸', '⏸')
        
        # Fix the broken voices loading line
        content = re.sub(
            r'if \(window\.speechSynthesis\{[^}]*\}',
            '''if (window.speechSynthesis) {
      window.speechSynthesis.getVoices();
      let voicesLoaded = false;
      window.speechSynthesis.onvoiceschanged = function() {
        if (!voicesLoaded) {
          voicesLoaded = true;
          console.log('[Vocal Congés] Voix chargées:', window.speechSynthesis.getVoices().length);
        }
      };
    }''',
            content
        )
        
        # Improve the lireAvecNavigateur function
        old_function = r'function lireAvecNavigateur\(\)\{[^}]*\{[^}]*\}[^}]*\}'
        new_function = '''function lireAvecNavigateur(){
    if (!window.speechSynthesis){ 
      btnTxt.textContent='Non supporté'; 
      return; 
    }
    
    // Arrêter toute synthèse en cours
    window.speechSynthesis.cancel();
    
    // Attendre un peu pour que le cancel soit effectif
    setTimeout(function() {
      var texte = RAPPORT || ('Rapport congés : ' + t + ' demandes. ' + a + ' approuvées, ' + r + ' refusées, ' + p + ' en attente.');
      
      // Nettoyer le texte pour éviter les erreurs de synthèse
      texte = texte.replace(/[^\\w\\s\\.,!?;:-]/g, ' ')
                   .replace(/\\s+/g, ' ')
                   .trim();
      
      // Limiter la longueur
      if (texte.length > 800) {
        texte = texte.substring(0, 797) + '...';
      }
      
      console.log('[Vocal Congés] Texte:', texte);
      
      var utter = new SpeechSynthesisUtterance(texte);
      utter.lang = 'fr-FR'; 
      utter.rate = 0.9; 
      utter.volume = 1;
      utter.pitch = 1;
      
      // Gestionnaires d'événements améliorés
      utter.onstart = function(){ 
        console.log('[Vocal Congés] ✅ Démarré');
        btnIcon.className='fas fa-volume-high'; 
        btnTxt.textContent='⏸ Pause'; 
        btnVoice.disabled = false;
      };
      
      utter.onend = function(){ 
        console.log('[Vocal Congés] ✅ Terminé');
        btnIcon.className='fas fa-volume-high'; 
        btnTxt.textContent='🤖 Rejouer'; 
        btnVoice.disabled=false; 
        _paused=false; 
      };
      
      utter.onerror = function(e){ 
        console.error('[Vocal Congés] ❌ Erreur:', e.error);
        btnIcon.className='fas fa-exclamation-triangle'; 
        btnTxt.textContent='Erreur : '+e.error; 
        btnVoice.disabled=false;
        _paused=false;
        
        // Retry avec un texte plus simple en cas d'erreur
        if (e.error === 'synthesis-failed' || e.error === 'synthesis-unavailable') {
          setTimeout(function() {
            var texteSimple = 'Rapport vocal indisponible.';
            var utterSimple = new SpeechSynthesisUtterance(texteSimple);
            utterSimple.lang = 'fr-FR';
            utterSimple.rate = 0.8;
            window.speechSynthesis.speak(utterSimple);
          }, 1000);
        }
      };
      
      function parler(){
        var voices = window.speechSynthesis.getVoices();
        console.log('[Vocal Congés] Voix disponibles:', voices.length);
        
        // Chercher une voix française fiable
        var frVoice = voices.find(function(v){ 
          return v.lang==='fr-FR' && !v.name.includes('Google'); 
        }) || voices.find(function(v){ 
          return v.lang.startsWith('fr'); 
        }) || voices.find(function(v){
          return v.default;
        });
        
        if (frVoice) {
          utter.voice = frVoice;
          console.log('[Vocal Congés] Voix sélectionnée:', frVoice.name);
        }
        
        btnIcon.className='fas fa-spinner fa-spin';
        btnTxt.textContent='Démarrage...';
        btnVoice.disabled = true;
        
        try {
          window.speechSynthesis.speak(utter);
        } catch (error) {
          console.error('[Vocal Congés] Erreur speak:', error);
          btnIcon.className='fas fa-exclamation-triangle';
          btnTxt.textContent='Erreur technique';
          btnVoice.disabled=false;
        }
      }
      
      // Vérifier si les voix sont chargées
      var voices = window.speechSynthesis.getVoices();
      if (voices.length > 0) { 
        parler(); 
      } else { 
        var voicesLoaded = false;
        window.speechSynthesis.onvoiceschanged = function() {
          if (!voicesLoaded) {
            voicesLoaded = true;
            parler();
          }
        };
        
        // Timeout de sécurité
        setTimeout(function() {
          if (!voicesLoaded) {
            voicesLoaded = true;
            parler();
          }
        }, 2000);
      }
    }, 200);
  }'''
        
        # Apply the function replacement (more flexible pattern)
        content = re.sub(
            r'function lireAvecNavigateur\(\)\{.*?else \{ window\.speechSynthesis\.onvoiceschanged = parler; \}\s*\}',
            new_function,
            content,
            flags=re.DOTALL
        )
        
        with open(conge_file, 'w', encoding='utf-8') as f:
            f.write(content)
        
        print(f"✅ Fixed {conge_file}")
    
    print("✅ Web Speech API fixes completed!")
    print("\n🎯 Key improvements:")
    print("- Added proper error handling for synthesis-failed errors")
    print("- Improved text cleaning to prevent synthesis issues")
    print("- Added retry mechanism with simpler text on failure")
    print("- Better voice selection avoiding problematic voices")
    print("- Added timeouts and safety mechanisms")
    print("- Fixed corrupted emoji characters")
    print("- Enhanced logging for debugging")

if __name__ == "__main__":
    fix_web_speech_api()