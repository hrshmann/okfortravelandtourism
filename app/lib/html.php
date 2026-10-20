<?php
declare(strict_types=1);

/**
 * Whitelist HTML sanitiser for rich text written in the admin editor.
 * Anything not listed here is unwrapped (tags) or dropped (attributes).
 */
const HTML_ALLOWED = [
    'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
    'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'hr' => [],
    'a' => ['href', 'title', 'target', 'rel'], 'img' => ['src', 'alt', 'width', 'height'],
    'figure' => [], 'figcaption' => [], 'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
];
const HTML_DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript'];

function clean_html(?string $html): string
{
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }
    if (!preg_match('/<[a-z][\s\S]*>/i', $html)) {
        return text_to_html($html);
    }
    if (!class_exists('DOMDocument')) {
        return text_to_html(strip_tags($html));
    }
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__root') ?: $doc->documentElement;
    clean_node($root);
    $out = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}

function clean_node(DOMNode $node): void
{
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child instanceof DOMComment) {
            $node->removeChild($child);
            continue;
        }
        if (!$child instanceof DOMElement) {
            continue;
        }
        $tag = strtolower($child->tagName);
        if (in_array($tag, HTML_DROP_WITH_CONTENT, true)) {
            $node->removeChild($child);
            continue;
        }
        clean_node($child);
        if (!array_key_exists($tag, HTML_ALLOWED)) {
            // Unwrap: keep the text, lose the tag (e.g. <div>, <span> pasted from Word).
            $isBlock = in_array($tag, ['div', 'section', 'article', 'header', 'footer'], true);
            if ($isBlock && $child->childNodes->length) {
                $p = $node->ownerDocument->createElement('p');
                while ($child->firstChild) {
                    $p->appendChild($child->firstChild);
                }
                $node->replaceChild($p, $child);
            } else {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
            }
            continue;
        }
        foreach (iterator_to_array($child->attributes) as $attr) {
            $name = strtolower($attr->name);
            if (!in_array($name, HTML_ALLOWED[$tag], true)) {
                $child->removeAttribute($attr->name);
                continue;
            }
            if (in_array($name, ['href', 'src'], true) && !safe_url($attr->value, $name === 'src')) {
                $child->removeAttribute($attr->name);
            }
        }
        if ($tag === 'a') {
            if ($child->getAttribute('target') === '_blank' || preg_match('#^https?://#', $child->getAttribute('href'))) {
                $child->setAttribute('rel', 'noopener');
            }
        }
        if ($tag === 'img') {
            $child->setAttribute('loading', 'lazy');
        }
    }
}

function safe_url(string $url, bool $image = false): bool
{
    $url = trim(html_entity_decode($url));
    if ($url === '') {
        return false;
    }
    if (preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, '/') || str_starts_with($url, '#')) {
        return true;
    }
    if (!$image && preg_match('#^(mailto|tel):#i', $url)) {
        return true;
    }
    // Relative paths like "uploads/2026/10/x.webp" are fine; anything with a scheme (javascript:, data:) is not.
    return !preg_match('#^[a-z][a-z0-9+.-]*:#i', $url);
}

function text_to_html(string $text): string
{
    $paras = preg_split('/\n\s*\n/', str_replace("\r", '', trim($text)));
    return implode("\n", array_map(fn($p) => '<p>' . nl2br(e(trim($p)), false) . '</p>', array_filter($paras, fn($p) => trim($p) !== '')));
}

/** Plain-text reading time for blog posts. */
function reading_minutes(?string $html): int
{
    $words = str_word_count(strip_tags((string) $html));
    return max(1, (int) round($words / 220));
}
