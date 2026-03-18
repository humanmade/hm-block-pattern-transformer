---
layout: home
title: About this plugin
nav_order: 1
permalink: /
---

# Rehydrator

Rehydrator is a PHP library for WordPress that makes it easier to write content migration scripts targeting block-theme sites. It provides a fluent API for loading registered block patterns, populating them with content from your source data, and serializing the result to block markup ready to write to `post_content`.

## The problem it solves

> _Most of the time, humankind must collectively dehydrate and be stored. When a long Stable Era arrives, they collectively revive through rehydration. Then they proceed to build and produce._
> 
>   -- Cixin Liu, [_The Three-Body Problem_](https://reactormag.com/the-three-body-problem-excerpt-king-wen-of-zhou-and-the-long-night-cixin-liu/)

When migrating a classic WordPress site to a block theme, the destination content needs to land in the right structural positions within the new design — not just as a flat husk of old content and meta data. This plugin provides a stable utility API to make scripting migrations into rich block templates simpler and easier to iterate and maintain.

On the new block-theme site, a post's layout can be defined as a [block pattern](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-patterns/): a registered arrangement of blocks that serves as the template for each post type. For example, an article post template could contain a heading, featured image slot, table of contents, body area, and footer CTA each at a specific place in that structure to guide editors through creating the post. 

A content migration script needs to do the same thing the block editor does when a user fills in a new post — populate each of those slots with the right content. Rehydrator gives you an API to do that programmatically, targeting blocks by their pattern origin, block type, and position.

## How it works

> _He’ll recover soon enough, when we soak him in water. It’s just like soaking dried mushrooms._

The plugin's transformation model works in four steps:

1. **Start with a pattern** that defines your target layout. This is the block structure you want every migrated post to end up with.
2. **Resolve nested patterns** — your top-level layout pattern likely references other patterns (`theme/hero`, `theme/content-section`, etc.). The plugin flattens these into a single block tree, tracking which pattern each block originated from.
3. **Apply transformations** — you declare what content goes where, targeting blocks by pattern slug, block type, and occurrence index.
4. **Get the result** — serialized block markup, ready to write to `post_content`.

```php
use HM\Rehydrator\Template;

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
