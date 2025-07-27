#!/bin/bash

set -e

echo "🔨 Building Phreakscope C Extension"
echo "==================================="

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check dependencies
echo "Checking build dependencies..."

if ! command -v phpize &> /dev/null; then
    echo -e "${RED}❌ phpize not found${NC}"
    echo "Install php-dev package:"
    echo "  Ubuntu/Debian: sudo apt-get install php-dev"
    echo "  CentOS/RHEL: sudo yum install php-devel"
    echo "  Alpine: apk add php-dev"
    exit 1
fi

if ! command -v gcc &> /dev/null; then
    echo -e "${RED}❌ gcc not found${NC}"
    echo "Install build tools:"
    echo "  Ubuntu/Debian: sudo apt-get install build-essential"
    echo "  CentOS/RHEL: sudo yum groupinstall 'Development Tools'"
    echo "  Alpine: apk add build-base"
    exit 1
fi

echo -e "${GREEN}✅ Build dependencies OK${NC}"

# Build extension
cd ext/phreakscope

echo
echo "Building extension..."

# Clean previous build
if [ -f "Makefile" ]; then
    echo "Cleaning previous build..."
    make clean || true
    phpize --clean || true
fi

echo "Running phpize..."
phpize

echo "Configuring..."
./configure

echo "Compiling..."
make -j$(nproc)

echo -e "${GREEN}✅ Extension built successfully${NC}"

# Test loading
echo
echo "Testing extension loading..."

if php -d extension=./modules/phreakscope.so -m | grep -q phreakscope; then
    echo -e "${GREEN}✅ Extension loads correctly${NC}"
else
    echo -e "${RED}❌ Extension failed to load${NC}"
    exit 1
fi

# Show extension info
echo
echo "Extension information:"
php -d extension=./modules/phreakscope.so -r "phpinfo();" | grep -A 10 "phreakscope"

echo
echo -e "${YELLOW}Installation options:${NC}"
echo
echo "1. Install system-wide (requires sudo):"
echo "   sudo make install"
echo "   echo 'extension=phreakscope.so' | sudo tee /etc/php/conf.d/phreakscope.ini"
echo
echo "2. Load manually for testing:"
echo "   php -d extension=$(pwd)/modules/phreakscope.so your_script.php"
echo
echo "3. Add to php.ini:"
echo "   extension=$(pwd)/modules/phreakscope.so"
echo
echo -e "${GREEN}🎉 Build complete!${NC}"
echo
echo "Next steps:"
echo "1. Install the extension (see options above)"
echo "2. Run tests: ./run_tests.sh"
echo "3. Try the demo: cd docker && docker-compose up -d"