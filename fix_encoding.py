#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys

# Read the file
with open('templates/conge_tt/index.html.twig', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix the encoding issues
content = content.replace("Ã¢─¬─", "-")
content = content.replace("ÃÂ ", "à")
content = content.replace("ÃÂ´", "ô")
content = content.replace("Ã─°", "É")
content = content.replace("Ã¢─¢Â", "═")

# Write back
with open('templates/conge_tt/index.html.twig', 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Encoding fixed!")
