<?php

namespace Drupal\wmsearch\Service;

/**
 * Quick'n dirty html2text
 * TODO improve/test.
 */
class HtmlStripper
{
    protected static $inline = [
        '#text' => true,
        'a' => true,
        'b' => true,
        'big' => true,
        'i' => true,
        'small' => true,
        'tt' => true,
        'abbr' => true,
        'acronym' => true,
        'cite' => true,
        'code' => true,
        'dfn' => true,
        'em' => true,
        'kbd' => true,
        'strong' => true,
        'samp' => true,
        'time' => true,
        'var' => true,
        'bdo' => true,
        'img' => true,
        'map' => true,
        'object' => true,
        'q' => true,
        'script' => true,
        'span' => true,
        'sub' => true,
        'sup' => true,
        'button' => true,
        'label' => true,
    ];

    protected $disallowed = [];

    public function strip(
        $html,
        array $disallowedContentTags = [
            'textarea',
            'input',
            'select',
            'script',
            'style',
            'link',
            'iframe',
            'canvas',
            'object',
        ]
    ) {
        foreach ($disallowedContentTags as $tag) {
            $this->disallowed[$tag] = true;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML($html);

        $data = '';
        foreach ($dom->childNodes as $node) {
            $data .= $this->inner($node);
        }

        return $data;
    }

    protected function inner(\DOMNode $node)
    {
        if (isset($this->disallowed[strtolower($node->nodeName)])) {
            return '';
        }

        if ($node->nodeType === XML_TEXT_NODE) {
            return $node->textContent;
        }

        if (!$node->hasChildNodes()) {
            return '';
        }

        $data = '';
        $breaked = true;
        foreach ($node->childNodes as $child) {
            if ($txt = trim($this->inner($child), "\n ")) {
                $inline = isset(static::$inline[strtolower($child->nodeName)]);
                $data .= ($breaked || $inline ? '' : "\n") .
                    $txt .
                    ($inline ? ' ' : "\n");

                $breaked = !$inline;
            }
        }

        return $data;
    }
}

