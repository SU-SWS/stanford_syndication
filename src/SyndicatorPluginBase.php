<?php

namespace Drupal\stanford_syndication;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for syndicator plugins.
 */
abstract class SyndicatorPluginBase extends PluginBase implements SyndicatorInterface {

  use StringTranslationTrait;

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

}
