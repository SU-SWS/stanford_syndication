<?php

namespace Drupal\Tests\stanford_syndication\Kernel\EventSubscriber;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\stanford_syndication\Events\SyndicationEntityActionEvent;
use Drupal\stanford_syndication\Events\SyndicationEvents;
use GuzzleHttp\ClientInterface;

class SyndicationEventSubscriberTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'node',
    'stanford_syndication',
    'user',
  ];

  protected function setUp(): void {
    parent::setUp();

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')
      ->will($this->returnCallback([$this, 'guzzleRequest']));
    \Drupal::getContainer()->set('http_client', $client);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig('system');
    NodeType::create(['type' => 'foo'])->save();
    \Drupal::configFactory()->getEditable('stanford_syndication.settings')
      ->set('enabled', TRUE)
      ->set('syndicators', [
        'stanford_enterprise' => [
          'id' => 'stanford_enterprise',
          'node_types' => ['foo'],
          'webhook' => 'bar',
        ],
      ])->save(TRUE);
    \Drupal::state()->set('stanford_enterprise.token', 'foobar');
  }

  public function testEventSubscriber() {
    $this->assertNull(\Drupal::state()->get('testEventSubscriber'));
    $node = Node::create([
      'type' => 'foo',
      'title' => 'bar',
      'syndication' => TRUE,
    ]);
    $event = new SyndicationEntityActionEvent($node, 'insert');
    \Drupal::service('event_dispatcher')
      ->dispatch($event, SyndicationEvents::ENTITY_ACTION);
    $this->assertTrue(\Drupal::state()->get('testEventSubscriber'));

    \Drupal::state()->delete('testEventSubscriber');

    $event = new SyndicationEntityActionEvent($node, 'update');
    \Drupal::service('event_dispatcher')
      ->dispatch($event, SyndicationEvents::ENTITY_ACTION);
    $this->assertTrue(\Drupal::state()->get('testEventSubscriber'));

    \Drupal::state()->delete('testEventSubscriber');
    $event = new SyndicationEntityActionEvent($node, 'delete');
    \Drupal::service('event_dispatcher')
      ->dispatch($event, SyndicationEvents::ENTITY_ACTION);
    $this->assertNull(\Drupal::state()->get('testEventSubscriber'));
  }

  public function guzzleRequest() {
    \Drupal::state()->set('testEventSubscriber', TRUE);
  }

}
