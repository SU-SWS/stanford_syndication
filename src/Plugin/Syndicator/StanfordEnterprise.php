<?php

namespace Drupal\stanford_syndication\Plugin\Syndicator;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_syndication\SyndicatorPluginBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation for Enterprise News.
 *
 * @Syndicator(
 *   id = "stanford_enterprise",
 *   label = @Translation("Stanford Enterprise News"),
 *   description = @Translation("News Syndication webhook.")
 * )
 */
class StanfordEnterprise extends SyndicatorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Current site domain.
   *
   * @var string
   */
  protected string $domain;

  /**
   * Configured site name.
   *
   * @var string
   */
  protected string $siteName;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ClientInterface $client, protected StateInterface $state, protected EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->domain = $requestStack->getCurrentRequest()->getHost();
    $this->siteName = $configFactory->get('system.site')->get('name');
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return ['webhook' => '', 'node_types' => []];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($form, $form_state);
    $node_types = [];
    foreach ($this->entityTypeManager->getStorage('node_type')
      ->loadMultiple() as $id => $type) {
      $node_types[$id] = $type->label();
    }

    $element['node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node Types'),
      '#default_value' => $this->getConfiguration()['node_types'] ?? [],
      '#options' => $node_types,
    ];
    $element['webhook'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook URL'),
      '#default_value' => $this->getConfiguration()['webhook'],
    ];
    $element['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Access Token'),
      '#default_value' => $this->state->get('stanford_enterprise.token'),
    ];

    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $token = $form_state->getValue('access_token');
    $form_state->unsetValue('access_token');
    if(empty($token)){
      $this->state->delete('stanford_enterprise.token');
    }
    else {
      $this->state->set('stanford_enterprise.token', $token);
    }

    $chosen_types = array_values(array_filter($form_state->getValue('node_types')));
    $form_state->setValue('node_types', $chosen_types);
    if (empty($chosen_types)) {
      $this->setConfiguration([]);
      return;
    }

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function insert(NodeInterface $node): void {
    $access_token = $this->state->get('stanford_enterprise.token');
    if (
      !in_array($node->bundle(), $this->getConfiguration()['node_types']) ||
      !$this->getConfiguration()['webhook'] ||
      !$access_token
    ) {
      return;
    }

    $options = [
      'timeout' => 1,
      'headers' => [
        'Content-Type' => 'application/json',
        'X-Webhook-Token' => $this->state->get('stanford_enterprise.token'),
      ],
      'body' => json_encode([
        'cms_type' => 'Drupal 9',
        'domain' => $this->domain,
        'site_name' => $this->siteName,
        'content_type' => $node->bundle(),
        'type' => 'story',
        'id' => $node->uuid(),
      ]),
    ];
    try {
      $this->client->request('POST', $this->getConfiguration()['webhook'], $options);
    }
    catch (\Throwable $e) {
      // The response will time out because the webhook triggers functionality on the
      // vendor that fetches all the jsonapi data and returns it in the response. We
      // don't care about the response data so we can ignore the error.
    }
  }

  /**
   * {@inheritDoc}
   */
  public function update(NodeInterface $node): void {
    $this->insert($node);
  }

}

