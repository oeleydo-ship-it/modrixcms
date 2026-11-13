<?php

namespace Tests\Unit;

use App\Cms\Extensions\VersionConstraint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionConstraintTest extends TestCase
{
    /** @return array<string, array{string, string, bool}> */
    public static function cases(): array
    {
        return [
            'any' => ['1.4.0', '*', true],
            'exact' => ['1.0.0', '1.0.0', true],
            'exact miss' => ['1.0.1', '1.0.0', false],
            'caret same major' => ['1.9.3', '^1.0', true],
            'caret next major' => ['2.0.0', '^1.0', false],
            'caret below' => ['0.9.0', '^1.0', false],
            'caret zero minor' => ['0.3.9', '^0.3', true],
            'caret zero minor next' => ['0.4.0', '^0.3', false],
            'tilde two parts' => ['1.9.0', '~1.2', true],
            'tilde three parts' => ['1.2.9', '~1.2.3', true],
            'tilde three parts next minor' => ['1.3.0', '~1.2.3', false],
            'range' => ['1.5.0', '>=1.2 <2.0', true],
            'range miss' => ['2.1.0', '>=1.2 <2.0', false],
            'prerelease tag ignored' => ['1.2.0-beta.1', '^1.0', true],
        ];
    }

    #[DataProvider('cases')]
    public function test_constraints(string $version, string $constraint, bool $expected): void
    {
        $this->assertSame($expected, VersionConstraint::satisfies($version, $constraint));
    }

    public function test_rejects_malformed_input(): void
    {
        $this->assertFalse(VersionConstraint::isVersion('1.0'));
        $this->assertFalse(VersionConstraint::isConstraint('^1.0 || ^2.0'));
        $this->assertFalse(VersionConstraint::satisfies('abc', '*'));
        $this->assertSame(1, VersionConstraint::compare('1.10.0', '1.9.0'));
    }
}
