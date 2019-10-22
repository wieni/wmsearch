<?php

namespace Drupal\wmsearch\EventSubscriber;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\wmsearch\WmsearchEvents;
use Drupal\wmsearch\Event\MappingEvent;

class StopwordsMappingSubscriber implements EventSubscriberInterface
{
    const ANALYZERS_WITH_STOPWORDS = [
        'html',
        'ngram',
        'standard_synonym',
    ];

    const LANGCODE_STOPWORDS_MAPPING = [
        'ar' => ['_arabic_'],
        'hy' => ['_armenian_'],
        'eu' => ['_basque_'],
        'bn' => ['_bengali_'],
        'bg' => ['_bulgarian_'],
        'ca' => ['_catalan_'],
        'cs' => ['_czech_'],
        'da' => ['_danish_'],
        'nl' => ['_dutch_'],
        'en' => ['_english_'],
        'fi' => ['_finnish_'],
        'gl' => ['_galician_'],
        'de' => ['_german_'],
        'el' => ['_greek_'],
        'hi' => ['_hindi_'],
        'hu' => ['_hungarian_'],
        'in' => ['_indonesian_'],
        'ga' => ['_irish_'],
        'it' => ['_italian_'],
        'lv' => ['_latvian_'],
        'no' => ['_norwegian_'],
        'fa' => ['_persian_'],
        'pt' => ['_portuguese_', '_brazilian_'],
        'ro' => ['_romanian_'],
        'ru' => ['_russian_'],
        'ku' => ['_sorani_'],
        'es' => ['_spanish_'],
        'sv' => ['_swedish_'],
        'th' => ['_thai_'],
        'tr' => ['_turkish_'],
    ];

    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var StateInterface */
    protected $state;

    public function __construct(
        LanguageManagerInterface $languageManager,
        StateInterface $state
    ) {
        $this->languageManager = $languageManager;
        $this->state = $state;
    }

    public static function getSubscribedEvents()
    {
        return [
            WmsearchEvents::MAPPING => 'onMapping',
        ];
    }

    public static function getDefaultLists()
    {
        $lists = array_values(self::LANGCODE_STOPWORDS_MAPPING);
        $lists = array_merge(...$lists);
        $lists = array_unique($lists);

        return $lists;
    }

    public function onMapping(MappingEvent $event): void
    {
        $mapping = $event->getMapping();

        foreach ($this->languageManager->getLanguages() as $language) {
            $whitelist = $this->state->get('wmsearch.stopwords.whitelist', []);
            $blacklist = $this->state->get('wmsearch.stopwords.blacklist', []);

            $lists = self::LANGCODE_STOPWORDS_MAPPING[$language->getId()] ?? [];
            $lists = array_merge($lists, $whitelist);
            $lists = array_diff($lists, $blacklist);
            $lists = array_unique($lists);

            foreach ($lists as $list) {
                $filterName = 'stopwords_' . trim($list, '_');
                $mapping['settings']['analysis']['filter'][$filterName] = [
                    'type' => 'stop',
                    'stopwords' => $list,
                ];

                foreach (self::ANALYZERS_WITH_STOPWORDS as $analyzerName) {
                    $mapping['settings']['analysis']['analyzer'][$analyzerName]['filter'][] = $filterName;
                }

            }
        }

        $event->setMapping($mapping);
    }
}
