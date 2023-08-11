<?php

namespace Drupal\Tests\stanford_syndication\Unit\Plugin\Syndicator;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\stanford_syndication\Plugin\Syndicator\StanfordEnterprise;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Test for the syndicator plugin.
 */
class StanfordEnterpriseTest extends UnitTestCase {

  /**
   * Plugin to test.
   *
   * @var \Drupal\stanford_syndication\Plugin\Syndicator\StanfordEnterprise
   */
  protected $plugin;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')
      ->will($this->returnCallback([$this, 'guzzleRequest']));
    $state = $this->createMock(StateInterface::class);

    $nodeTypes = [
      'foo' => $this->createMock(NodeTypeInterface::class),
      'bar' => $this->createMock(NodeTypeInterface::class),
    ];

    $entityStorage = $this->createMock(EntityStorageInterface::class);
    $entityStorage->method('loadMultiple')->willReturn($nodeTypes);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($entityStorage);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn('Foo Bar Site');

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $request_stack = new RequestStack();
    $request_stack->push(new Request());

    $container = new ContainerBuilder();
    $container->set('http_client', $client);
    $container->set('state', $state);
    $container->set('entity_type.manager', $entityTypeManager);
    $container->set('request_stack', $request_stack);
    $container->set('config.factory', $configFactory);
    $container->set('string_translation', $this->getStringTranslationStub());

    \Drupal::setContainer($container);

    $config = [
      'node_types' => ['foo'],
      'webhook' => 'bar',
      'access_token' => 'foo',
    ];
    $def = ['label' => 'foo'];
    $this->plugin = StanfordEnterprise::create($container, $config, '', $def);
  }

  public function testInsert() {
    // Node is not configured to be used.
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('bar');
    $this->assertNull($this->plugin->insert($node));

    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('foo');
    $this->plugin->insert($node);

    $this->plugin->setConfiguration(['webhook' => 'exception'] + $this->plugin->getConfiguration());
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('foo');
    $this->expectException(ClientException::class);
    $this->plugin->insert($node);
  }

  public function testUpdate() {
    $this->plugin->setConfiguration(['webhook' => 'exception'] + $this->plugin->getConfiguration());
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('foo');
    $this->expectException(ClientException::class);
    $this->plugin->update($node);
  }

  public function guzzleRequest($method, $uri, $options) {
    if ($uri === 'exception') {
      throw new ClientException('Failed', $this->createMock(RequestInterface::class));
    }
  }

  public function testFormMethods() {
    $this->assertNotEmpty($this->plugin->label());
    $form = [];
    $form_state = new FormState();
    $elements = $this->plugin->buildConfigurationForm($form, $form_state);
    $this->assertTrue(in_array('foo', $elements['node_types']['#default_value']));

    $this->plugin->validateConfigurationForm($form, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());

    $form_state->setValue('webhook', 'foobar');
    $form_state->setValue('node_types', ['foo']);
    $this->assertNotEquals('foobar', $this->plugin->getConfiguration()['webhook']);
    $this->plugin->submitConfigurationForm($form, $form_state);
    $this->assertEquals('foobar', $this->plugin->getConfiguration()['webhook']);
  }

}
