# HM Block Pattern Transformer

Pattern-based content transformation for WordPress block migrations. Provides utilities for loading block patterns, resolving nested pattern references, and applying targeted transformations to populate templates with content.

## Installation

### As a WordPress Plugin

1. Clone or download this repository to your `wp-content/plugins/` directory
2. Activate the plugin in WordPress admin

### Via Composer

```bash
composer require humanmade/block-pattern-transformer
```

## Requirements

- PHP 8.0+
- WordPress 6.0+

## Quick Start

```php
use HM\Block_Pattern_Transformer\Template;

// Load a pattern template and transform it
$transformer = new Template( 'theme/template-article' );

$content = $transformer
    ->replace_text( 'theme/hero', 'core/heading', 0, 'Article Title' )
    ->replace_text( 'theme/hero', 'core/paragraph', 0, 'Article description' )
    ->replace_placeholder( 'content-placeholder', $content_blocks )
    ->get_content();

// Use the transformed content
wp_update_post( [
    'ID' => $post_id,
    'post_content' => $content,
] );
```

## Namespaces

The plugin provides four namespaces:

| Namespace | Purpose |
|-----------|---------|
| `HM\Block_Pattern_Transformer\ACF` | Extract data from ACF blocks |
| `HM\Block_Pattern_Transformer\Blocks` | Create blocks programmatically |
| `HM\Block_Pattern_Transformer\Pattern_Transformer` | Load and transform patterns |
| `HM\Block_Pattern_Transformer\Synced_Patterns` | Create and reference synced patterns |

Plus the `Template` class for the fluent API:

```php
use HM\Block_Pattern_Transformer\Template;
```

## API Reference

### ACF Namespace

Extract data from Advanced Custom Fields blocks.

```php
use HM\Block_Pattern_Transformer\ACF;

// Extract a single field
$title = ACF\extract_field( $block, 'title', 'Default Title' );

// Extract repeater field data
$stats = ACF\extract_repeater( $block, 'featured_stats', [ 'number', 'label' ] );
// Returns: [
//   [ 'number' => '95%', 'label' => 'User satisfaction' ],
//   [ 'number' => '50+', 'label' => 'Countries served' ],
// ]
```

### Blocks Namespace

Create WordPress blocks programmatically.

```php
use HM\Block_Pattern_Transformer\Blocks;

// Create a heading block
$heading = Blocks\create_heading( 'My Heading', 2 );

// Create a paragraph block
$paragraph = Blocks\create_paragraph( 'Some content here.' );

// Create multiple paragraphs from text with line breaks
$paragraphs = Blocks\create_paragraphs( $multi_paragraph_text );

// Create a custom block
$block = Blocks\create_block( 'theme/hero', [ 'className' => 'is-featured' ] );

// Create a block with wrapper HTML and inner blocks
$group = Blocks\create_wrapper_block(
    'core/group',
    '<div class="wp-block-group">',
    '</div>',
    [ 'className' => 'my-group' ],
    $inner_blocks
);

// Strip HTML from a value
$plain_text = Blocks\strip_html( '<p>Some <strong>content</strong></p>' );
```

### Pattern_Transformer Namespace

Load patterns and apply transformations.

```php
use HM\Block_Pattern_Transformer\Pattern_Transformer;

// Get pattern content by slug
$content = Pattern_Transformer\get_pattern_by_slug( 'theme/hero' );

// Resolve nested pattern references
$blocks = parse_blocks( $content );
$resolved = Pattern_Transformer\resolve_and_tag_patterns( $blocks );

// Apply transformations
$transformations = [
    'theme/hero' => [
        'core/heading' => [
            0 => [ 'textContent' => 'New Title' ],
        ],
    ],
];
$transformed = Pattern_Transformer\apply_pattern_transformations( $resolved, $transformations );

// Update block text content safely (preserves HTML structure)
$block = Pattern_Transformer\update_block_text_content( $block, 'New text' );

// Rebuild innerContent after modifying innerBlocks
$block = Pattern_Transformer\rebuild_inner_content( $block );
```

### Synced_Patterns Namespace

Work with synced (reusable) patterns.

```php
use HM\Block_Pattern_Transformer\Synced_Patterns;

// Get or create a synced pattern from a registered pattern
$synced_id = Synced_Patterns\get_or_create( 'theme/footer-cta' );

// Create a block reference to a synced pattern
$block = Synced_Patterns\create_block_reference( $synced_id );
```

### Template Class (Fluent API)

The recommended way to transform patterns with a clean, chainable API.

```php
use HM\Block_Pattern_Transformer\Template;

$transformer = new Template( 'theme/template-article' );

$content = $transformer
    // Replace text in specific blocks
    ->replace_text( 'theme/hero', 'core/heading', 0, 'Article Title' )
    ->replace_text( 'theme/hero', 'core/paragraph', 0, 'Description' )

    // Replace block attributes
    ->replace_attributes( 'theme/hero', 'core/image', 0, [
        'id' => $image_id,
        'url' => $image_url,
    ] )

    // Apply custom transformation logic
    ->transform_callback( 'theme/stats', 'core/columns', function( $block ) use ( $stats ) {
        // Modify columns based on stats data
        return $modified_block;
    } )

    // Remove blocks conditionally
    ->remove_if_empty( 'theme/hero', 'core/paragraph', 1, $subtitle )
    ->remove_if( 'theme/video', 'core/group', 0, fn() => empty( $video_url ) )

    // Replace placeholder with content
    ->replace_placeholder( 'content-placeholder', $content_blocks )

    // Convert pattern references to synced patterns
    ->replace_with_synced_pattern( 'theme/footer-cta' )

    // Get the final content
    ->get_content();

// Check for errors
if ( $transformer->has_error() ) {
    $error = $transformer->get_error();
    // Handle error...
}

// Or get blocks array for further processing
$blocks = $transformer->get_blocks();
```

## Pattern Structure

### Template Patterns

Template patterns can reference other patterns:

```html
<!-- wp:pattern {"slug":"theme/hero"} /-->
<!-- wp:pattern {"slug":"theme/content-section"} /-->
<!-- wp:pattern {"slug":"theme/footer-cta"} /-->
```

### Content Placeholders

Use blocks with `metadata.name` to mark content insertion points:

```html
<!-- wp:paragraph {"metadata":{"name":"content-placeholder"}} -->
<p></p>
<!-- /wp:paragraph -->
```

Then replace with:

```php
$transformer->replace_placeholder( 'content-placeholder', $blocks );
```

### Transformation Targeting

Transformations target blocks by:
1. **Pattern slug** - Which pattern the block came from
2. **Block type** - The block's name (e.g., `core/heading`)
3. **Occurrence** - Zero-indexed position within that pattern

```php
// Target the first heading in 'theme/hero'
->replace_text( 'theme/hero', 'core/heading', 0, 'Title' )

// Target the second paragraph in 'theme/hero'
->replace_text( 'theme/hero', 'core/paragraph', 1, 'Subtitle' )
```

## Use Cases

### Content Migration

Transform old content structures into new pattern-based layouts:

```php
// Parse old content
$old_blocks = parse_blocks( $old_post->post_content );

// Extract data from ACF blocks
$title = ACF\extract_field( $hero_block, 'title' );
$stats = ACF\extract_repeater( $hero_block, 'stats', [ 'number', 'label' ] );

// Transform into new pattern
$transformer = new Template( 'theme/template-article' );
$new_content = $transformer
    ->replace_text( 'theme/hero', 'core/heading', 0, $title )
    ->replace_placeholder( 'content', $content_blocks )
    ->get_content();
```

### Dynamic Content Generation

Generate content from external data sources:

```php
$transformer = new Template( 'theme/product-page' );
$content = $transformer
    ->replace_text( 'theme/hero', 'core/heading', 0, $product->name )
    ->replace_text( 'theme/hero', 'core/paragraph', 0, $product->description )
    ->replace_attributes( 'theme/hero', 'core/image', 0, [
        'url' => $product->image_url,
        'alt' => $product->name,
    ] )
    ->transform_callback( 'theme/specs', 'core/table', function( $block ) use ( $product ) {
        // Populate specifications table
        return $block;
    } )
    ->get_content();
```

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
