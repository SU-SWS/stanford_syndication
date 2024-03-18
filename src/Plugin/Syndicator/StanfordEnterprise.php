<?php

namespace Drupal\stanford_syndication\Plugin\Syndicator;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
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
class StanfordEnterprise extends SyndicatorPluginBase {

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
      $container->get('logger.factory'),
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, protected ClientInterface $client, protected StateInterface $state, protected EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory);
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
      '#default_value' => Settings::get('stanford_syndication.stanford_enterprise.token', $this->state->get('stanford_enterprise.token')),
      '#attributes' => ['disabled' => !!Settings::get('stanford_syndication.stanford_enterprise.token')],
    ];

    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $token = $form_state->getValue('access_token');
    $form_state->unsetValue('access_token');

    if (empty($token)) {
      $this->state->delete('stanford_enterprise.token');
    }
    else {
      // Don't set the state if the Settings are used.
      if (Settings::get('stanford_syndication.stanford_enterprise.token') != $token) {
        $this->state->set('stanford_enterprise.token', $token);
      }
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
    $access_token = Settings::get('stanford_syndication.stanford_enterprise.token', $this->state->get('stanford_enterprise.token'));
    $webhook = $this->getConfiguration()['webhook'];
    if (
      !in_array($node->bundle(), $this->getConfiguration()['node_types']) ||
      !$webhook ||
      !$access_token
    ) {
      if ($this->debug) {
        $this->logger->info('Syndication not enabled. Node "%nid", Webhook: "%webhook", Token: "%token"', ['%nid' => $node->id(), '%webhook' => $webhook, '%token' => $access_token]);
      }
      return;
    }

    $options = [
      'timeout' => 2,
      'headers' => [
        'Content-Type' => 'application/json',
        'X-Webhook-Token' => $access_token,
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
      $this->client->request('POST', $webhook, $options);
    }
    catch (\Throwable $e) {
      // The response will time out because the webhook triggers functionality on the
      // vendor that fetches all the jsonapi data and returns it in the response. We
      // don't care about the response data, so we can ignore the error unless we
      // are debugging.
      if ($this->debug) {
        $this->logger->info('Syndication error: %error', ['%error' => $e->getMessage()]);
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function update(NodeInterface $node): void {
    $this->insert($node);
  }

}

