<?php

namespace Drupal\wmsearch\Form;

use Drupal\wmsearch\Service\Api;
use Drupal\wmsearch\Exception\ApiException;
use Drupal\wmsearch\Entity\Query\Query;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SimpleSearchForm extends FormBase
{
    /** @var Api */
    protected $api;

    /** @var RequestStack */
    protected $req;

    /** @var TranslationManager */
    protected $trans;

    public function __construct(
        Api $api,
        RequestStack $req,
        TranslationManager $trans
    ) {
        $this->api = $api;
        $this->req = $req;
        $this->trans = $trans;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('wmsearch.api'),
            $container->get('request_stack'),
            $container->get('string_translation')
        );
    }

    public function getFormId()
    {
        return 'wmsearch_simple_form';
    }

    public function buildForm(
        array $form,
        FormStateInterface $formState
    ) {
        $q = $this->req->getCurrentRequest()->query;

        $query = $q->get('query', '');
        $page = $q->get('page', 0);
        if ($_query = $formState->getValue('query', '')) {
            $query = $_query;
            $page = 0;
        }

        $q->set('page', $page);

        $form['query'] = [
            '#type' => 'textfield',
            '#title' => 'Search',
            '#default_value' => $query,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->trans->translate('Submit'),
            '#button_type' => 'primary',
        ];

        $perPage = 1;
        $total = 0;
        if (!empty($query)) {
            $q = (new Query('page'))
                ->from($perPage * $page)
                ->size($perPage)
                ->setHighlight(1, 120, ['title', 'body'], '<em>', '</em>')
                ->addMultiMatch($query, ['title', 'body']);

            try {
                $this->search($form, $q);
            } catch (ApiException $e) {
                $form['error']['#markup'] = sprintf(
                    '<p class="warning">%s</p>',
                    $this->trans->translate('Something went wrong, try again later')
                );
            }
        }

        pager_default_initialize($total, $perPage);
        $form['pager'] = [
            '#type' => 'pager',
            '#parameters' => ['query' => $query],
        ];

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
        $formState->setRebuild();
    }

    protected function search(array &$form, Query $q)
    {
        $r = $this->api->highlightSearch($q);
        $form['results'] = ['#type' => 'container'];

        $total = $r->getTotal(); //$results['hits']['total'];
        $rows = ['#theme' => 'item_list', '#items' => []];
        foreach ($r->getHits() as $hit) {
            $rows['#items'][] = [
                '#markup' => sprintf(
                    '<a href="%s"><h3>%s</h3><p>%s</p></a>',
                    $hit->getSource('url') ?? '/',
                    $hit->getHighlights('title')[0] ?? $hit->getSource('title'),
                    $hit->getHighlights('body')[0] ?? ''
                ),
            ];
        }

        $form['results'] = [
            '#type' => 'container',
            'rows' => $rows,
        ];
    }
}

