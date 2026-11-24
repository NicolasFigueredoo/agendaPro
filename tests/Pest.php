<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)
    ->beforeEach(fn () => $this->withoutVite()) // los tests no necesitan npm run dev / build
    ->in('Feature');

uses(Tests\TestCase::class)->in('Unit');
