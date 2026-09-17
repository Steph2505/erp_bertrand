<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * L'accueil ("/") est le tableau de bord, protégé par l'auth : un visiteur
     * non connecté doit être redirigé vers la page de login, pas voir un 200.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
