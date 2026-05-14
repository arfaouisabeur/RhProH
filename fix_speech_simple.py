#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os

def fix_conge_template():
    """Fix the conge template Web Speech API issues"""
    
    conge_file = 'templates/conge_tt/index.html.twig'
    if not os.path.exists(conge_file):
        print(f"❌ File {conge_file} not found")
        return
    
    print(f"📝 Fixing {conge_file}...")
    
    with open(conge_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Fix corrupted emojis
    content = content.replace('ð¤', '🤖')
    content = content.replace('-¸', '⏸')
    
    # Fix the broken voices loading line - find and replace the malformed line
    lines = content.split('\n')
    for i, line in enumerate(lines):
        if 'if (window.speechSynthesis{' in line:
            lines[i] = '''    if (window.speechSynthesis) {
      window.speechSynthesis.getVoices();
      let voicesLoaded = false;
      window.speechSynthesis.onvoiceschanged = function() {
        if (!voicesLoaded) {
          voicesLoaded = true;
          console.log('[Vocal Congés] Voix chargées:', window.speechSynthesis.getVoices().length);
        }
      };
    }'''
            break
    
    content = '\n'.join(lines)
    
    with open(conge_file, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"✅ Fixed {conge_file}")

if __name__ == "__main__":
    fix_conge_template()
    print("✅ Simple fixes completed!")