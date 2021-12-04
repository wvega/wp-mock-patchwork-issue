<?php

use PHPUnit\Util\Test;
use WP_Mock\Tools\TestCase;

class ExampleTest extends TestCase
{
    public function testMockStaticMethod()
    {
        $this->mockStaticMethod(Example::class, 'foo')->andReturnNull();
    }

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

	/**
	 * Mock a static method of a class
	 *
	 * @param string      $class  The classname or class::method name
	 * @param null|string $method The method name. Optional if class::method used for $class
	 *
	 * @return \Mockery\Expectation
	 * @throws Exception
	 */
	protected function mockStaticMethod( $class, $method = null ) {
		if ( ! $method ) {
			list( $class, $method ) = ( explode( '::', $class ) + array( null, null ) );
		}
		if ( ! $method ) {
			throw new Exception( sprintf( 'Could not mock %s::%s', $class, $method ) );
		}
		if ( ! WP_Mock::usingPatchwork() || ! function_exists( 'Patchwork\redefine' ) ) {
			throw new Exception( 'Patchwork is not loaded! Please load patchwork before mocking static methods!' );
		}

		$safe_method = "wp_mock_safe_$method";
		$signature   = md5( "$class::$method" );
		if ( ! empty( $this->mockedStaticMethods[ $signature ] ) ) {
			$mock = $this->mockedStaticMethods[ $signature ];
		} else {

			$rMethod = false;
			if ( class_exists( $class ) ) {
				$rMethod = new ReflectionMethod( $class, $method );
			}
			if (
				$rMethod &&
				(
					! $rMethod->isUserDefined() ||
					! $rMethod->isStatic() ||
					$rMethod->isPrivate()
				)
			) {
				throw new Exception( sprintf( '%s::%s is not a user-defined non-private static method!', $class, $method ) );
			}

			/** @var \Mockery\Mock $mock */
			$mock = Mockery::mock( $class );
			$mock->shouldAllowMockingProtectedMethods();
			$this->mockedStaticMethods[ $signature ] = $mock;

			\Patchwork\redefine( "$class::$method", function () use ( $mock, $safe_method ) {
				return call_user_func_array( array( $mock, $safe_method ), func_get_args() );
			} );
		}
		$expectation = $mock->shouldReceive( $safe_method );

		return $expectation;
	}
}
