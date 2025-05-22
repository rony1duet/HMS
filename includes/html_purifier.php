<?php

/**
 * Helper function to safely display HTML content from Quill.js editor
 * Filters out potentially dangerous HTML elements and attributes while keeping the formatting
 */
function purify_html($html)
{
    // List of allowed HTML tags
    $allowedTags = [
        'p',
        'br',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'strong',
        'em',
        'u',
        's',
        'blockquote',
        'pre',
        'ul',
        'ol',
        'li',
        'span',
        'a',
        'img',
        'table',
        'thead',
        'tbody',
        'tr',
        'td',
        'th',
        'div',
        'code',
        'b',
        'i'
    ];

    // Convert allowed tags array into the format needed for strip_tags
    $allowedTagsString = '<' . implode('><', $allowedTags) . '>';

    // Filter HTML using strip_tags with allowed tags
    $filteredHtml = strip_tags($html, $allowedTagsString);

    // Basic regex patterns to filter out potentially harmful attributes like inline JavaScript
    $patterns = [
        // Remove javascript: protocol from attributes
        '/javascript\s*:/i',
        // Remove onload, onclick, and other JS event handlers
        '/\son\w+\s*=/i',
        // Remove data: protocol in img src (can be used for XSS)
        '/src\s*=\s*["\']data:(?!image\/)/i',
    ];

    // Replace patterns with empty string
    foreach ($patterns as $pattern) {
        $filteredHtml = preg_replace($pattern, '', $filteredHtml);
    }

    return $filteredHtml;
}
