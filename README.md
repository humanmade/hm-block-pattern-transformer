# Rehydrator

Pattern-based content transformation for WordPress block migrations. Provides utilities for loading block patterns, resolving nested pattern references, and applying targeted transformations to populate templates with content.

**[Full documentation →](https://humanmade.github.io/rehydrator/)**

## Installation

**Via Composer:**

```bash
composer require humanmade/rehydrator
```

**As a WordPress plugin:** Clone or download this repository to `wp-content/plugins/` and activate.

## Requirements

- PHP 8.0+
- WordPress 6.0+

## Quick Start

```php
use HM\Rehydrator\Template;

$transformer = new Template( 'theme/template-article' );

$content = $transformer
    ->replace_text( 'theme/hero', 'core/heading', occurrence: 0, text: $title )
    ->replace_text( 'theme/hero', 'core/paragraph', occurrence: 0, text: $standfirst )
    ->replace_attributes( 'theme/hero', 'core/image', occurrence: 0, attrs: [
        'id'  => $image_id,
        'url' => $image_url,
    ] )
    ->replace_placeholder( 'content-placeholder', $body_blocks )
    ->get_content();

wp_update_post( [
    'ID'           => $post_id,
    'post_content' => $content,
] );
```

For full API documentation including all transformation methods and helper namespaces, see the **[docs site](https://humanmade.github.io/rehydrator/)**.

## Development

### Running Tests

```bash
composer install
composer test
```

### Code Style

```bash
composer lint
```

## License

GPL-2.0-or-later
