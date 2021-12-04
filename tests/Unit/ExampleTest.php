<?php

use PHPUnit\Util\Test;
use WP_Mock\Tools\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Backported from PHPUnit 9.4 TestCase class.
     *
     * WP_Mock's TestCase expects this method to be present on {@see setUpContentFiltering()}.
     * 
     * This workaround won't be necessary once https://github.com/10up/wp_mock/pull/164 is merged.
     *
     * @return array
     */
    public function getAnnotations() : array
    {
        return Test::parseTestMethodAnnotations(
            static::class,
            $this->getName(false)
        );
    }
}
