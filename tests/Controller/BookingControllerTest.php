<?php
/**
 * Created by PhpStorm.
 * User: saverio
 * Date: 03/04/18
 * Time: 15.04
 */

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{

    /**
     * @test
     */
    public function it_should_create_booking()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $container->get('doctrine.dbal.default_connection')->query('truncate booking');

        $client->request('POST', '/booking', [], [], [], json_encode([
            "idUser" => 1,
            "from" => "2018-04-03 18:00",
            "to" => "2018-04-03 19:00"
        ]));

        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $booking = $container->get('App\Domain\Repository\BookingRepository')->find(
            json_decode($client->getResponse()->getContent(), true)["bookingId"]
        );

        $this->assertEquals(1, $booking->getIdUser());
        $this->assertEquals("2018-04-03 18:00", $booking->getFrom()->format('Y-m-d H:i'));
        $this->assertEquals("2018-04-03 19:00", $booking->getTo()->format('Y-m-d H:i'));
    }

    /**
     * @test
     */
    public function it_should_fail_when_booking_slots_are_overlapping()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $container->get('doctrine.dbal.default_connection')->query('truncate booking');

        $client->request('POST', '/booking', [], [], [], json_encode([
            "idUser" => 1,
            "from" => "2018-04-03 18:00",
            "to" => "2018-04-03 20:00"
        ]));

        $client->request('POST', '/booking', [], [], [], json_encode([
            "idUser" => 2,
            "from" => "2018-04-03 18:00",
            "to" => "2018-04-03 19:00"
        ]));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(
            'Slot not available',
            json_decode($client->getResponse()->getContent(), true)["message"]
        );

    }

    /**
     * @test
     */
    public function it_should_fail_when_booking_slot_are_shorter_than_1h()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $container->get('doctrine.dbal.default_connection')->query('truncate booking');

        $client->request('POST', '/booking', [], [], [], json_encode([
            "idUser" => 1,
            "from" => "2018-04-03 19:00",
            "to" => "2018-04-03 19:30"
        ]));

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals(
            'Slot must be length min 1 hour and max 3 hours',
            json_decode($client->getResponse()->getContent(), true)["message"]
        );

    }

    /**
     * @test
     */
    public function it_should_fail_when_booking_slot_are_longer_than_3h()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $container->get('doctrine.dbal.default_connection')->query('truncate booking');

        $client->request('POST', '/booking', [], [], [], json_encode([
            "idUser" => 1,
            "from" => "2018-04-03 18:00",
            "to" => "2018-04-03 22:00"
        ]));

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals(
            'Slot must be length min 1 hour and max 3 hours',
            json_decode($client->getResponse()->getContent(), true)["message"]
        );

    }
}
