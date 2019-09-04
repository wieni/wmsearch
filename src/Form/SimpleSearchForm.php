<?php

namespace Drupal\wmsearch\Form;

use Drupal\wmsearch\Exception\ApiException;
use Drupal\wmsearch\Service\Api\SearchApi;
use Drupal\wmsearch\Service\QueryBuilderInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SimpleSearchForm extends FormBase
{
    /** @var SearchApi */
    protected $searchApi;
    /** @var RequestStack */
    protected $request;
    /** @var TranslationManager */
    protected $trans;
    /** @var QueryBuilderInterface */
    protected $builder;

    public function __construct(
        SearchApi $searchApi,
        RequestStack $request,
        QueryBuilderInterface $builder,
        TranslationManager $trans
    ) {
        $this->searchApi = $searchApi;
        $this->request = $request;
        $this->builder = $builder;
        $this->trans = $trans;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('wmsearch.api.search'),
            $container->get('request_stack'),
            $container->get('wmsearch.json.query_builder'),
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
        $q = $this->request->getCurrentRequest()->query;

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

        if (!empty($query)) {
            try {
                $form += $this->search($query, $page);
            } catch (ApiException $e) {
                $form['error']['#markup'] = sprintf(
                    '<p class="warning">%s</p>',
                    $this->trans->translate('Something went wrong, try again later')
                );
            }
        }

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
        $formState->setRebuild();
    }

    protected function search($query, $page = 0)
    {
        $perPage = 10;
        $q = $this->builder->build($query, $page * $perPage, $perPage);
        $r = $this->searchApi->highlightSearch($q);
        $form['results'] = ['#type' => 'container'];

        $total = $r->getTotal();
        $rows = ['#theme' => 'item_list', '#items' => []];
        foreach ($r->getHits() as $hit) {
            $rows['#items'][] = [
                '#markup' => sprintf(
                    '<a href="%s"><h3>%s</h3><p>%s</p></a>',
                    $hit->getSource('url') ?? '/',
                    $hit->getHighlights('title')[0] ?? $hit->getSource('title'),
                    $hit->getHighlights('intro')[0] ?? ($hit->getHighlights('body')[0] ?? '')
                ),
            ];
        }

        $form['results'] = [
            '#type' => 'container',
            'rows' => $rows,
        ];

        pager_default_initialize($total, $perPage);
        $form['pager'] = [
            '#type' => 'pager',
            '#parameters' => ['query' => $query],
        ];

        return $form;
    }
}

