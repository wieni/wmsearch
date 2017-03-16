<?php

namespace Drupal\wmsearch\Entity\Result;

class Suggestion extends Document
{
    /**
     * @return string
     */
    public function getSuggestion()
    {
        return $this->data['text'];
    }
}

