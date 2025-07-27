#!/bin/bash

set -e

echo "🧪 Phreakscope Test Suite"
echo "========================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

failed=0
total=0

run_test() {
    local test_name="$1"
    local test_cmd="$2"
    
    echo
    echo -e "${YELLOW}Running: $test_name${NC}"
    echo "----------------------------------------"
    
    total=$((total + 1))
    
    if eval "$test_cmd"; then
        echo -e "${GREEN}✅ $test_name PASSED${NC}"
    else
        echo -e "${RED}❌ $test_name FAILED${NC}"
        failed=$((failed + 1))
    fi
}

# Test 1: C Extension Basic Test
run_test "C Extension Basic Test" "php tests/extension/basic_test.php"

# Test 2: PHP CollapsedEncoder Test
run_test "PHP CollapsedEncoder Test" "php tests/php/collapsed_encoder_test.php"

# Test 3: Go Agent Tests
if command -v go &> /dev/null; then
    run_test "Go Agent Tests" "cd tests/agent && go test -v"
else
    echo -e "${YELLOW}⚠️  Go not found, skipping Go agent tests${NC}"
fi

# Test 4: Integration Test (requires C extension and pcntl)
if php -m | grep -q pcntl; then
    run_test "Integration Test" "php tests/integration/full_flow_test.php"
else
    echo -e "${YELLOW}⚠️  pcntl extension not found, skipping integration test${NC}"
fi

# Summary
echo
echo "========================================="
echo "Test Summary:"
echo "  Total: $total"
echo "  Passed: $((total - failed))"
echo "  Failed: $failed"

if [ $failed -eq 0 ]; then
    echo -e "${GREEN}🎉 All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}💥 $failed test(s) failed${NC}"
    exit 1
fi