# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please report it responsibly.

### How to Report

1. **Do NOT** create a public GitHub issue for security vulnerabilities
2. Email the maintainer directly at: **methorz@spammerz.de**
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 7 days
- **Resolution Timeline**: Depends on severity (critical: ASAP, high: 30 days, medium: 90 days)

### After Resolution

- Security fixes will be released as patch versions
- Credit will be given to reporters (unless anonymity is requested)
- A security advisory will be published for significant vulnerabilities

## Security Best Practices

When using this package:

- **Keep dependencies updated** - Run `composer update` regularly
- **Use latest PHP version** - Security fixes are backported to supported versions only
- **Validate all input** - The DTO mapper handles untrusted HTTP request data
- **Use validation constraints** - Always validate DTOs with Symfony Validator
- **Sanitize output** - DTOs may contain user input; sanitize when rendering

## Known Security Considerations

This package:

- **Handles untrusted HTTP input** - Request data is mapped to DTOs; always validate
- **Reflection-based mapping** - Uses PHP reflection; ensure DTO classes are trusted
- **JSON serialization** - Response DTOs are serialized; avoid exposing sensitive fields
- **Type coercion** - Automatic type conversion may have edge cases; test thoroughly

### Input Validation Example

```php
// Always use validation constraints on DTO properties
final readonly class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public string $name,

        #[Assert\Email]
        public string $email,
    ) {}
}
```

## Contact

- **Security Issues**: methorz@spammerz.de
- **General Issues**: [GitHub Issues](https://github.com/MethorZ/http-dto/issues)

---

Thank you for helping keep this project secure!

