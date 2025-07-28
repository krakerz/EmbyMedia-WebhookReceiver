# Contributing to EmbyMedia-WebhookReceiver

Thank you for your interest in contributing to EmbyMedia-WebhookReceiver! This document provides guidelines and information for contributors.

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js and npm
- MySQL/SQLite database
- Git

### Development Setup

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/your-username/EmbyMedia-WebhookReceiver.git
   cd EmbyMedia-WebhookReceiver
   ```

3. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

4. **Set up environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure your `.env` file** with appropriate database and API settings

6. **Run migrations**:
   ```bash
   php artisan migrate
   ```

7. **Run tests** to ensure everything works:
   ```bash
   php artisan test
   ```

8. **Start development server**:
   ```bash
   php artisan serve
   npm run dev
   ```

## Development Guidelines

### Code Style

- Follow **PSR-12** coding standards
- Use **meaningful variable and method names**
- Add **PHPDoc comments** for all public methods and classes
- Keep methods focused and single-purpose
- Use **type hints** for parameters and return values

#### Example:
```php
/**
 * Process webhook data and extract metadata
 * 
 * @param array $payload The webhook payload from Emby
 * @param string $eventType The type of event being processed
 * @return array Extracted and formatted metadata
 * 
 * @throws \InvalidArgumentException When payload is invalid
 */
public function processWebhookData(array $payload, string $eventType): array
{
    // Implementation here
}
```

### Git Workflow

1. **Create a feature branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** with clear, focused commits:
   ```bash
   git add .
   git commit -m "Add feature: description of what you added"
   ```

3. **Write or update tests** for your changes

4. **Ensure all tests pass**:
   ```bash
   php artisan test
   ```

5. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request** on GitHub

### Commit Messages

Use clear, descriptive commit messages:

- ✅ `Add support for music library webhooks`
- ✅ `Fix image fetching for TV episodes`
- ✅ `Update README with new configuration options`
- ❌ `fix bug`
- ❌ `update stuff`

### Testing

- **Write tests** for all new functionality
- **Update existing tests** when modifying behavior
- **Ensure all tests pass** before submitting PR
- **Test both happy path and error cases**

#### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/EmbyWebhookTest.php

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel (faster)
php artisan test --parallel
```

#### Writing Tests

```php
<?php

namespace Tests\Feature;

use App\Models\EmbyWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YourFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_your_feature_works_correctly(): void
    {
        // Arrange
        $payload = ['Event' => 'test.event'];
        
        // Act
        $response = $this->postJson('/emby/webhook', $payload);
        
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('emby_webhooks', [
            'event_type' => 'test.event'
        ]);
    }
}
```

## Types of Contributions

### 🐛 Bug Fixes

- Fix issues reported in GitHub Issues
- Include test cases that reproduce the bug
- Explain the root cause in your PR description

### ✨ New Features

- Discuss major features in an issue first
- Follow existing patterns and conventions
- Include comprehensive tests
- Update documentation

### 📚 Documentation

- Improve README clarity
- Add code comments
- Update API documentation
- Fix typos and grammar

### 🎨 UI/UX Improvements

- Follow existing design patterns
- Ensure responsive design
- Test on multiple screen sizes
- Consider accessibility

## Areas for Contribution

### High Priority
- **Additional Image Providers**: Support for more metadata sources
- **Enhanced Filtering**: More sophisticated dashboard filters
- **Performance Optimization**: Caching, database optimization
- **Mobile Experience**: Improved responsive design

### Medium Priority
- **Webhook Event Types**: Support for more Emby events
- **Configuration UI**: Web-based configuration interface
- **Export Features**: Data export functionality
- **Notification System**: Email/Slack notifications

### Low Priority
- **Themes**: Dark/light mode toggle
- **Statistics**: Usage analytics and charts
- **Backup/Restore**: Data backup functionality
- **Multi-language**: Internationalization support

## Pull Request Process

### Before Submitting

1. ✅ All tests pass
2. ✅ Code follows style guidelines
3. ✅ Documentation is updated
4. ✅ No merge conflicts with main branch
5. ✅ Feature is complete and tested

### PR Description Template

```markdown
## Description
Brief description of changes made.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Documentation update
- [ ] Performance improvement
- [ ] Refactoring

## Testing
- [ ] Tests added/updated
- [ ] All tests pass
- [ ] Manual testing completed

## Screenshots (if applicable)
Add screenshots for UI changes.

## Related Issues
Closes #123
```

### Review Process

1. **Automated checks** must pass (tests, style)
2. **Code review** by maintainers
3. **Testing** of functionality
4. **Documentation review**
5. **Merge** when approved

## Code Architecture

### Directory Structure

```
app/
├── Http/Controllers/     # Request handling
├── Models/              # Database models
├── Services/            # Business logic
└── Providers/           # Service providers

resources/
├── views/               # Blade templates
└── js/                  # Frontend assets

tests/
├── Feature/             # Integration tests
└── Unit/                # Unit tests

config/                  # Configuration files
database/               # Migrations and seeders
```

### Key Components

- **EmbyWebhookController**: Main webhook processing
- **ImageFetchingService**: Cover image retrieval
- **EmbyWebhook Model**: Database representation
- **Service Classes**: External API integration

## Getting Help

### Resources

- 📖 [Laravel Documentation](https://laravel.com/docs)
- 📖 [API Documentation](docs/API.md)
- 🐛 [GitHub Issues](https://github.com/krakerz/EmbyMedia-WebhookReceiver/issues)

### Questions

- **General questions**: Create a GitHub Discussion
- **Bug reports**: Create a GitHub Issue
- **Feature requests**: Create a GitHub Issue with feature request template

## Recognition

Contributors will be recognized in:
- README acknowledgments
- Release notes
- GitHub contributors list

Thank you for contributing to EmbyMedia-WebhookReceiver! 🎉