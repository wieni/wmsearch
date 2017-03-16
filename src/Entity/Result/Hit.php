<?php

namespace Drupal\wmsearch\Entity\Result;

class Hit extends Document
{
    /**
     * @return array
     */
    public function getHighlights($field = '')
    {
        if (!isset($this->data['highlight'])) {
            return [];
        }

        if (!$field) {
            return $this->data['highlight'];
        }

        return $this->data['highlight'][$field] ?? [];
    }
}

