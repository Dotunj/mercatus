<?php

namespace Tests\Browser;

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\DuskTestCase;

class WaitlistDuskTest extends DuskTestCase
{
    use DatabaseMigrations, WithFaker;

    /** @test */
    public function a_user_can_be_subscribed_to_the_waitlist()
    {
        $this->browse(function ($browser) {
            $browser->visit('/')
                    ->assertSee('Licensed agricultural or extracted products')
                    ->type('email', 'dotunj@gmail.com')
                    ->press('Request early access')
                    ->assertPathIs('/subscribed')
                    ->assertSee('Hooray! You’re on the waitlist');
        });
    }

    /** @test */
    public function a_user_can_see_the_subscribed_page()
    {
        $this->browse(function ($browser) {
            $browser->visit('/subscribed')
                    ->assertSee('Hooray! You’re on the waitlist')
                    ->assertSee(config('mail.from.address'));
        });
    }

    /** @test */
    public function a_unique_email_is_required()
    {
        $email = $this->faker->safeEmail;

        factory(Subscriber::class)->create(['email' => $email]);

        $this->browse(function ($browser) use ($email) {
            $browser->visit('/')
                    ->assertSee('Licensed agricultural or extracted products')
                    ->type('email', $email)
                    ->press('Request early access')
                    ->assertPathIs('/')
                    ->assertSee('The email has already been taken');
        });
    }

    /** @test
     *
     * @dataProvider invalidEmailsProvider
     */
    public function invalid_emails_are_not_accepted($email)
    {
        $this->browse(function ($browser) use ($email) {
            $browser->visit('/')
                    ->assertSee('Licensed agricultural or extracted products')
                    ->type('email', $email)
                    ->press('Request early access')
                    ->assertPathIs('/');
        });
    }

    public function invalidEmailsProvider()
    {
        return [
            ['dotun'],
            ['dotun//gmail.com']
        ];
    }
}
