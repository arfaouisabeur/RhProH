#!/bin/bash

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "=========================================="
echo "Doctrine Doctor - Application Complète des Corrections"
echo "=========================================="
echo ""
echo "Ce script va:"
echo "1. Vider le cache Symfony"
echo "2. Appliquer les corrections SQL"
echo "3. Mettre à jour le schéma Doctrine"
echo "4. Vérifier les corrections"
echo ""
echo -e "${RED}ATTENTION: Assurez-vous d'avoir une sauvegarde de votre base de données!${NC}"
echo ""
read -p "Appuyez sur Entrée pour continuer ou Ctrl+C pour annuler..."

# Step 1: Clear cache
echo ""
echo "=========================================="
echo "Étape 1/5: Vidage du cache"
echo "=========================================="
php bin/console cache:clear
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Cache vidé avec succès${NC}"
else
    echo -e "${RED}✗ Échec du vidage du cache${NC}"
    exit 1
fi

# Step 2: Apply SQL fixes
echo ""
echo "=========================================="
echo "Étape 2/5: Application des corrections SQL"
echo "=========================================="
echo "Connexion à MySQL..."
echo "Entrez le mot de passe MySQL quand demandé:"
mysql -u root -p pidevf < fix_doctrine_database_issues.sql
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Script SQL appliqué avec succès${NC}"
else
    echo -e "${RED}✗ Échec de l'application du script SQL${NC}"
    echo "Vérifiez vos identifiants MySQL et que la base 'pidevf' existe"
    exit 1
fi

# Step 3: Check schema differences
echo ""
echo "=========================================="
echo "Étape 3/5: Vérification du schéma"
echo "=========================================="
php bin/console doctrine:schema:update --dump-sql
echo ""
read -p "Voulez-vous appliquer ces changements? (o/N): " APPLY_SCHEMA
if [[ "$APPLY_SCHEMA" =~ ^[Oo]$ ]]; then
    php bin/console doctrine:schema:update --force
    echo -e "${GREEN}✓ Schéma mis à jour${NC}"
else
    echo -e "${YELLOW}⚠ Schéma non mis à jour${NC}"
fi

# Step 4: Validate schema
echo ""
echo "=========================================="
echo "Étape 4/5: Validation du schéma"
echo "=========================================="
php bin/console doctrine:schema:validate
echo ""

# Step 5: Run Doctrine Doctor
echo ""
echo "=========================================="
echo "Étape 5/5: Exécution de Doctrine Doctor"
echo "=========================================="
php bin/console doctrine:doctor
echo ""

# Summary
echo "=========================================="
echo -e "${GREEN}TERMINÉ!${NC}"
echo "=========================================="
echo ""
echo "Résultats:"
echo "  - Cache: Vidé"
echo "  - SQL: Appliqué"
echo "  - Schéma: Vérifié"
echo ""
echo "Prochaines étapes:"
echo "  1. Vérifiez les résultats de doctrine:doctor ci-dessus"
echo "  2. Testez votre application"
echo "  3. Si des erreurs persistent, consultez TROUBLESHOOTING.md"
echo ""
echo -e "${YELLOW}IMPORTANT - SÉCURITÉ:${NC}"
echo "N'oubliez pas de définir un mot de passe pour votre base de données!"
echo "Voir QUICK_FIX_GUIDE.md section 'CRITICAL SECURITY FIXES'"
echo ""
