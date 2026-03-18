---
layout: home
title: Getting Started
nav_order: 2
permalink: /getting-started
---

# Getting Started

## Installation

**Via Composer (recommended):**

```bash
composer require humanmade/rehydrator
```

**Or as a WordPress plugin:**

Clone or download the repository to `wp-content/plugins/` and activate it from the WordPress admin.

## Basic workflow

### 1. Design your target layout as patterns

Build the layout for each post type visually in the block editor and export the block markup as registered patterns in your theme. Patterns can be composed from smaller patterns using `wp:pattern` references:

```html
<!-- wp:pattern {"slug":"theme/hero"} /-->
<!-- wp:pattern {"slug":"theme/content-section"} /-->
<!-- wp:pattern {"slug":"theme/footer-cta"} /-->
```

### 2. Register the top-level pattern as the post type template

```php
register_post_type( 'article', [
    'template' => [
        [
            'core/pattern',
            [ 'slug' => 'theme/template-article' ],
        ],
    ],
] );
```

This is the same pattern your migration script will target — the editor and the migration use the same source of truth for layout.

### 3. Mark content insertion points with named placeholders

In your patterns, use `metadata.name` on any block to create a named placeholder. This marks where the migrated body content should be inserted:

```html
<!-- wp:paragraph {"metadata":{"name":"content-placeholder"}} -->
<p></p>
<!-- /wp:paragraph -->
```

### 4. Write your migration script

Use the `Template` class to load the pattern, apply your content, and get the result:

```php
use HM\Rehydrator\Template;
use HM\Rehydrator\Content_Parser;

// Convert the old post's classic HTML to blocks.
$body_blocks = Content_Parser\parse_content_with_conversion( $old_post->post_content );

// Populate the pattern with content.
$transformer = new Template( 'theme/template-article' );

$new_content = $transformer
    ->replace_text( 'theme/hero', 'core/heading', occurrence: 0, text: $old_post->post_title )
    ->replace_text( 'theme/hero', 'core/paragraph', occurrence: 0, text: get_post_meta( $old_post->ID, 'standfirst', true ) )
    ->replace_attributes( 'theme/hero', 'core/image', occurrence: 0, attrs: [
        'id'  => $featured_image_id,
        'url' => $featured_image_url,
    ] )
    ->remove_if_empty( 'theme/hero', 'core/paragraph', occurrence: 0, value: get_post_meta( $old_post->ID, 'standfirst', true ) )
    ->replace_placeholder( 'content-placeholder', $body_blocks )
    ->get_content();

// Write it back.
wp_update_post( [
    'ID'           => $new_post_id,
    'post_content' => $new_content,
] );
```

### 5. Run your migration

The plugin handles pattern resolution, transformation targeting, and serialization. The result is block markup that exactly matches the structure your block theme expects.

## Transformation targeting

Every transformation method targets a specific block by three coordinates:

| Coordinate | Description | Example |
|---|---|---|
| **Pattern slug** | Which pattern does the block live in? | `'theme/hero'` |
| **Block type** | What type of block is it? | `'core/heading'` |
| **Occurrence** | Which one, if there are multiple of that type in the pattern? (0-indexed) | `0` for the first, `1` for the second |

```php
// The first heading inside theme/hero
->replace_text( 'theme/hero', 'core/heading', occurrence: 0, text: $title )

// The second paragraph inside theme/hero
->replace_text( 'theme/hero', 'core/paragraph', occurrence: 1, text: $subtitle )
```

## When to use it

Rehydrator is purpose-built for **content migrations** where the destination site uses structured block layouts. It's most valuable when:

- You have multiple post types, each with a different block layout.
- Source content comes from mixed origins — classic editor HTML, ACF fields, custom post meta, external data sources.
- Layouts are complex and composed of reusable sections.
- You need the migration to be resilient to layout changes (update the pattern, re-run the migration).

It's also useful for **programmatic content generation** — generating post content from external data feeds, API responses, or any scenario where you need to populate a block layout without manual editing.
