---
layout: home
title: About this plugin
nav_order: 1
permalink: /
---

# Block Pattern Transformer

Block Pattern Transformer is a PHP library for WordPress that makes it easier to write content migration scripts targeting block-theme sites. It provides a fluent API for loading registered block patterns, populating them with content from your source data, and serializing the result to block markup ready to write to `post_content`.

## The problem it solves

When migrating a classic WordPress site to a block theme, the destination content needs to land in the right structural positions within the new design — not just as a flat dump of old HTML converted to paragraphs.

On the new block-theme site, a post's layout is defined as a [block pattern](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-patterns/): a registered arrangement of blocks that serves as the template for each post type. The heading, standfirst, featured image slot, body area, and footer CTA each have a specific place in that structure.

A content migration script needs to do the same thing the block editor does when a user fills in a new post — populate each of those slots with the right content. Block Pattern Transformer gives you an API to do that programmatically, targeting blocks by their pattern origin, block type, and position.

## How it works

The plugin's transformation model works in four steps:

1. **Start with a pattern** that defines your target layout. This is the block structure you want every migrated post to end up with.
2. **Resolve nested patterns** — your top-level layout pattern likely references other patterns (`theme/hero`, `theme/content-section`, etc.). The plugin flattens these into a single block tree, tracking which pattern each block originated from.
3. **Apply transformations** — you declare what content goes where, targeting blocks by pattern slug, block type, and occurrence index.
4. **Get the result** — serialized block markup, ready to write to `post_content`.

```php
use HM\Block_Pattern_Transformer\Template;

$transformer = new Template( 'theme/template-article' );

$content = $transformer
    ->replace_text( 'theme/hero', 'core/heading', occurrence: 0, text: $title )
    ->replace_text( 'theme/hero', 'core/paragraph', occurrence: 0, text: $standfirst )
    ->replace_attributes( 'theme/hero', 'core/image', occurrence: 0, attrs: [
        'id'  => $featured_image_id,
        'url' => $featured_image_url,
    ] )
    ->replace_placeholder( 'content-placeholder', $body_blocks )
    ->get_content();

wp_update_post( [
    'ID'           => $post_id,
    'post_content' => $content,
] );
```

## Requirements

- PHP 8.0+
- WordPress 6.0+
