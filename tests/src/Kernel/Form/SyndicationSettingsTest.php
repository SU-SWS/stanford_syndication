<?php

namespace Drupal\Tests\stanford_syndication\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\stanford_syndication\Form\SyndicationSettings;
use GuzzleHttp\ClientInterface;

class SyndicationSettingsTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'node',
    'stanford_syndication',
    'user',
  ];

  protected function setUp(): void {
    parent::setUp();
    NodeType::create(['type' => 'foo', 'name' => 'foo'])->save();
    $this->installConfig(['system', 'stanford_syndication']);

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')
      ->will($this->returnCallback([$this, 'guzzleRequest']));
    \Drupal::getContainer()->set('http_client', $client);
  }

  public function testForm() {
    $builder = \Drupal::formBuilder();
    $form_state = new FormState();
    $form = $builder->buildForm(SyndicationSettings::class, $form_state);
    $this->assertNotEmpty(Element::children($form['syndicators']));

    $form_state->setValue([
      'syndicators',
      'stanford_enterprise',
    ], ['node_types' => ['foo']]);
    $builder->submitForm(SyndicationSettings::class, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());

    $saved_config = \Drupal::config('stanford_syndication.settings')
      ->get('syndicators.stanford_enterprise.node_types');
    $this->assertTrue(in_array('foo', $saved_config));

    $form_state->setValue([
      'syndicators',
      'stanford_enterprise',
    ], ['node_types' => []]);
    $builder->submitForm(SyndicationSettings::class, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());

    $saved_config = \Drupal::config('stanford_syndication.settings')
      ->get('syndicators');
    $this->assertEmpty($saved_config);
  }

  public function guzzleRequest($method, $url, $options) {}

}
