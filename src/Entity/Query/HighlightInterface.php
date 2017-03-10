<?php

namespace Drupal\wmsearch\Entity\Query;

interface HighlightInterface extends QueryInterface
{
    public function hasHighlight();

    public function setHighlight(
        $amount,
        $size,
        array $fields,
        $preTag = '<em>',
        $postTag = '</em>'
    );

    public function getHighlightAmount();
    public function getHighlightSize();
    public function getHighlightFields();
    public function getHighlightPreTag();
    public function getHighlightPostTag();

}

