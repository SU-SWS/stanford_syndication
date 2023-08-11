<?php

namespace Drupal\stanford_syndication\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\stanford_syndication\SyndicatorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\SubformState;

/**
 * Configure stanford_syndication settings for this site.
 */
class SyndicationSettings extends ConfigFormBase {

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, protected StateInterface $state, protected EntityTypeManagerInterface $entityTypeManager, protected SyndicatorPluginManager $syndicatorPluginManagher) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.syndicator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stanford_syndication_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['stanford_syndication.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $defaults = $this->config('stanford_syndication.settings')->getRawData();
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable connection'),
      '#default_value' => $defaults['enabled'] ?? TRUE,
    ];;
    $form['syndicators_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Syndicators'),
      '#tree' => TRUE,
    ];
    $form['syndicators'] = ['#tree' => TRUE];

    foreach ($this->getSyndicatorPlugins($defaults['syndicators'] ?? []) as $plugin) {
      $plugin_form = [];
      $plugin_form_state = SubformState::createForSubform($plugin_form, $form, $form_state);
      /** @var \Drupal\stanford_syndication\SyndicatorInterface $plugin */
      $form['syndicators'][$plugin->getPluginId()] = [
        '#type' => 'details',
        '#title' => $plugin->label(),
        '#group' => 'syndicators_tabs',
        ...$plugin->buildConfigurationForm($plugin_form, $plugin_form_state),
      ];

      $form_state->set(['syndicators', $plugin->getPluginId()], $plugin);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->get('syndicators') as $plugin_id => $plugin) {
      $plugin_form_state = SubformState::createForSubform($form['syndicators'][$plugin_id], $form, $form_state);
      $plugin->validateConfigurationForm($form['syndicators'][$plugin_id], $plugin_form_state);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('stanford_syndication.settings')
      ->set('enabled', $form_state->getValue('enabled'));

    /** @var \Drupal\stanford_syndication\SyndicatorInterface $plugin */
    foreach ($form_state->get('syndicators') as $plugin_id => $plugin) {
      $plugin_form_state = SubformState::createForSubform($form['syndicators'][$plugin_id], $form, $form_state);
      $plugin->submitConfigurationForm($form['syndicators'][$plugin_id], $plugin_form_state);

      if ($config = $plugin->getConfiguration()) {
        $config['id'] = $plugin_id;
        $settings->set("syndicators.$plugin_id", $config);
      }
    }
    $settings->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Get the syndication plugins with their applicable configurations.
   *
   * @return \Drupal\stanford_syndication\SyndicatorInterface[]
   *   Keyed array of plugins.
   */
  protected function getSyndicatorPlugins($syndicators_configs) {
    $plugin_defs = $this->syndicatorPluginManagher->getDefinitions();
    $plugins = [];
    foreach (array_keys($plugin_defs) as $plugin_id) {
      $plugins[] = $this->syndicatorPluginManagher->createInstance($plugin_id, $syndicators_configs[$plugin_id] ?? []);
    }
    return $plugins;
  }

}
