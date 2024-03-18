<?php

namespace Drupal\stanford_syndication;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for syndicator plugins.
 */
abstract class SyndicatorPluginBase extends PluginBase implements SyndicatorInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * If debugging is enabled.
   *
   * @var bool
   */
  protected $debug = FALSE;

  /**
   * Logger channel service using the plugin id as the channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')
    );
  }

  /**
   * Plugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger_factory->get($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function insert(NodeInterface $node): void {}

  /**
   * {@inheritdoc}
   */
  public function update(NodeInterface $node): void {}

  /**
   * {@inheritdoc}
   */
  public function delete(NodeInterface $node): void {}

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function debug(bool $debug = TRUE): SyndicatorInterface {
    $this->debug = $debug;
    return $this;
  }

}
