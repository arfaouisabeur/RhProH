#!/bin/bash

echo "=========================================="
echo "Doctrine Doctor Fixes - Verification"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Clear cache
echo "Step 1: Clearing Symfony cache..."
php bin/console cache:clear
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Cache cleared successfully${NC}"
else
    echo -e "${RED}✗ Failed to clear cache${NC}"
    exit 1
fi
echo ""

# Step 2: Validate schema
echo "Step 2: Validating Doctrine schema..."
php bin/console doctrine:schema:validate
SCHEMA_RESULT=$?
echo ""

# Step 3: Run Doctrine Doctor
echo "Step 3: Running Doctrine Doctor..."
php bin/console doctrine:doctor
DOCTOR_RESULT=$?
echo ""

# Step 4: Check mapping info
echo "Step 4: Checking Doctrine mapping info..."
php bin/console doctrine:mapping:info
echo ""

# Summary
echo "=========================================="
echo "Verification Summary"
echo "=========================================="

if [ $SCHEMA_RESULT -eq 0 ]; then
    echo -e "${GREEN}✓ Schema validation: PASSED${NC}"
else
    echo -e "${YELLOW}⚠ Schema validation: WARNINGS (check output above)${NC}"
fi

if [ $DOCTOR_RESULT -eq 0 ]; then
    echo -e "${GREEN}✓ Doctrine Doctor: PASSED${NC}"
else
    echo -e "${YELLOW}⚠ Doctrine Doctor: WARNINGS (check output above)${NC}"
fi

echo ""
echo "=========================================="
echo "Next Steps"
echo "=========================================="
echo "1. Review any warnings above"
echo "2. If database changes needed, run:"
echo "   mysql -u root -p pidevf < fix_doctrine_database_issues.sql"
echo "3. If schema updates needed, run:"
echo "   php bin/console doctrine:schema:update --dump-sql"
echo "   php bin/console doctrine:schema:update --force"
echo ""
