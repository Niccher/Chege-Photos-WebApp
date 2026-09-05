# Testing & Quality Verification

How to execute syntax linters, PHPUnit test suites, and documentation quality checks.

---

## 1. PHP Syntax & Linter Verification

Verify the entire codebase for syntax errors using PHP's built-in CLI linter:
```bash
find app/ -name "*.php" -exec php -l {} \;
```

---

## 2. Running PHPUnit Tests

Execute automated test suites:
```bash
vendor/bin/phpunit
```

---

## 3. Documentation Quality Linting

Run the `project-docs` automated documentation linter:
```bash
python3 .agents/skills/project-docs/scripts/lint-docs.py .
```
Validates:
- `README.md` line count ($\le 150$ lines).
- Zero broken relative Markdown links.
- Valid Mermaid syntax blocks.
- No unredacted credentials or private keys.
