<?php

namespace Tests\Feature;

use App\Mail\SubscriberJoined;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WaitlistControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker, WithoutMiddleware;

    /** @test */
    public function it_returns_the_correct_view_for_joining_the_waitlist()
    {
        $this->get('/')->assertViewIs('waitlist');
    }

    /** @test */
    public function a_user_can_be_subscribed_to_the_waitlist()
    {
        Mail::fake();

        $subscriber = factory(Subscriber::class)->raw(['email' => $this->faker->safeEmail]);

        $this->post(route('waitlist'), $subscriber)->assertRedirect(route('subscribed'));

        $this->assertDatabaseHas('subscribers', $subscriber);

        Mail::assertQueued(SubscriberJoined::class, function ($mail) use ($subscriber) {
            $this->assertTrue($mail->hasTo(config('mail.from.address')), 'Unexpected To');
            $this->assertEquals($mail->subscriber->email, $subscriber['email']);

            return true;
        });
    }

    /** @test */
    public function it_returns_the_correct_view_when_a_user_has_been_subscribed()
    {
        $this->get(route('subscribed'))->assertViewIs('subscribed');
    }

    /** @test */
    public function an_email_address_is_required()
    {
        $subscriber = factory(Subscriber::class)->raw(['email' => '']);

        $this->post(route('waitlist'), $subscriber)->assertSessionHasErrors(['email']);

        $this->assertEquals(0, Subscriber::count());
    }

    /** @test */
    public function a_unique_email_address_is_required()
    {
        $email = $this->faker->safeEmail;

        factory(Subscriber::class)->create(['email' => $email]);

        $this->post(route('waitlist'), ['email' => $email])->assertSessionHasErrors(['email']);

        $this->assertEquals(1, Subscriber::count());
    }

    /** @test
     *
     * @dataProvider invalidEmailsProvider
     */
    public function invalid_emails_are_not_accepted($email)
    {
        $this->post(route('waitlist'), ['email' => $email])->assertSessionHasErrors('email');

        $this->assertEquals(0, Subscriber::count());
    }

    public function invalidEmailsProvider()
    {
        return [
            ['dotun'],
            ['dotun//gmail.com']
        ];
    }
}
