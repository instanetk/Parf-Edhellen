<?php

namespace Tests\Unit\Api;

use Illuminate\Support\Str;
use Tests\TestCase;
use App\Http\Controllers\Api\v2\BookApiController;
use App\Models\{
    Gloss
};

class BookApiControllerTest extends TestCase
{
    protected function setUp(): void
    {
        /**
         * This disables the exception handling to display the stacktrace on the console
         * the same way as it shown on the browser
         */
        parent::setUp();
        // $this->withoutExceptionHandling();
    }

    public function testSearchGroups()
    {
        $response = $this->getJson(route('api.book.groups'));
        $response->assertSuccessful();
    }

    public function testLanguages()
    {
        $response = $this->getJson(route('api.book.languages'));
        $response->assertSuccessful();
    }

    public function testGloss()
    {
        $gloss = Gloss::active()->first();
        $response = $this->getJson(route('api.book.gloss', ['glossId' => $gloss->id]));
        $response->assertSuccessful();
    }

    public function testEntities()
    {
        $this->assertTrue(true); // TODO
    }

    public function testTranslate()
    {
        $response = $this->postJson(route('api.book.translate', []));
        $response->assertStatus(422);

        // TODO: Actually test translating a word
    }

    public function testFind()
    {
        $response = $this->postJson(route('api.book.find', []));
        $response->assertStatus(422);

        // TODO: Actually test translating a word
    }
}
