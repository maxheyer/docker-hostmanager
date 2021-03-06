<?php
namespace ElevenLabs\DockerHostManager\Event;

use ElevenLabs\DockerHostManager\EventDispatcher\Event;
use ElevenLabs\DockerHostManager\EventDispatcher\EventType;
use PHPUnit\Framework\TestCase;

class SignedCertificateRemovedTest extends TestCase
{
    /** @test */
    public function it_implements_the_event_interface()
    {
        $event = new SignedCertificateRemoved('test');
        assertThat($event, isInstanceOf(Event::class));
    }

    /** @test */
    public function it_provide_a_name()
    {
        $event = new SignedCertificateRemoved('test');
        assertThat($event->getName(), equalTo('signed.certificate.removed'));
    }

    /** @test */
    public function it_provide_a_type()
    {
        $event = new SignedCertificateRemoved('test');
        assertThat($event->getType(), equalTo(new EventType(EventType::EVENT_STANDARD)));
    }

    /** @test */
    public function it_can_be_transformed_into_an_array()
    {
        $event = new SignedCertificateRemoved('test');
        assertThat($event->toArray(), equalTo(['containerName' => 'test']));
    }
}
