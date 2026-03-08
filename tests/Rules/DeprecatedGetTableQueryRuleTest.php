<?php

use Filacheck\Rules\DeprecatedGetTableQueryRule;

it('detects getTableQuery method', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('getTableQuery()', $violations);
});

it('passes when no getTableQuery exists', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;

class TestWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertNoViolations($violations);
});

it('marks as fixable when simple return and table method exist', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('marks as not fixable when body is complex', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        $query = Order::query();
        $query->where('active', true);

        return $query->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationCount(1, $violations);

    foreach ($violations as $violation) {
        expect($violation->isFixable)->toBeFalse();
    }
});

it('marks as not fixable when no table method exists', function () {
    $code = <<<'PHP'
<?php

use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationCount(1, $violations);

    foreach ($violations as $violation) {
        expect($violation->isFixable)->toBeFalse();
    }
});

it('marks as not fixable when table already has query call', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query())
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationCount(1, $violations);

    foreach ($violations as $violation) {
        expect($violation->isFixable)->toBeFalse();
    }
});

it('fixes by removing getTableQuery and inserting query into table chain', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->toContain('->query(fn (): Builder => Order::query()->latest())');
    expect($fixedCode)->not->toContain('getTableQuery');
});

it('handles multi-line return expression', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()
            ->where('active', true)
            ->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->toContain('->query(fn (): Builder => Order::query()');
    expect($fixedCode)->toContain('->latest())');
    expect($fixedCode)->not->toContain('getTableQuery');
});

it('preserves indentation when inserting query', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    // The ->query() should have the same indentation as ->columns()
    expect($fixedCode)->toContain("            ->query(fn (): Builder => Order::query()->latest())\n            ->columns([])");
});

it('adds Builder import when missing', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;

class TestWidget
{
    protected function getTableQuery()
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->toContain('use Illuminate\Database\Eloquent\Builder;');
    expect($fixedCode)->toContain('->query(fn (): Builder => Order::query()->latest())');
});

it('does not duplicate Builder import when already present', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect(substr_count($fixedCode, 'use Illuminate\Database\Eloquent\Builder;'))->toBe(1);
});
